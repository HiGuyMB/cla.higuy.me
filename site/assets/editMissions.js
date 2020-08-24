var missionList = [];
var pageSize = 20;

var filteredList = [];
var lastQuery = "";
var filterTimeout = 0;
var sort = {
	type: "name",
	direction: 1
};

var difficulties = [
	"??",
	"Very Easy",
	"Easy",
	"Easy-Medium",
	"Medium",
	"Medium-Hard",
	"Hard",
	"Very Hard",
	"Impossible"
];

var sorts = {
	"name": function(a, b, dir) {
		return a.sortName.localeCompare(b.sortName) * dir;
	},
	"difficulty": function(a, b, dir) {
		if (a.difficulty >= 8) {
			return 1;
		}
		if (b.difficulty >= 8) {
			return -1;
		}
		if (a.difficulty === b.difficulty) {
			return sorts.name(a, b, 1);
		}
		return (a.difficulty - b.difficulty) * dir;
	},
	"rating": function(a, b, dir) {
		if (a.rating < 1) {
			return 1;
		}
		if (b.rating < 1) {
			return -1;
		}
		if (a.rating === b.rating) {
			return sorts.name(a, b, 1);
		}
		return (b.rating - a.rating) * dir;
	},
	"addTime": function(a, b, dir) {
		var da = new Date(a.addTime);
		var db = new Date(b.addTime);
		if (da.valueOf() === db.valueOf()) {
			return sorts.name(a, b, 1);
		}
		return (db.valueOf() - da.valueOf()) * dir;
	}
};

var loadScreen = $("#loadScreen");
var mainScreen = $("#mainScreen");

function findMissionById(missionId) {
	return missionList.find(function(m) {
		return m.id === missionId;
	});
}

function findMissionRowById(missionId) {
	return $("#list").find("tr[data-mission-id="+missionId+"]");
}

function generatePagination(selected) {
	var pag = $("#pageSelector");
	pag.empty();
	var pageCount = Math.ceil(filteredList.length / pageSize);
	var pageList = range(0, pageCount).map(function(i) {
		return {
			index: i,
			display: i + 1,
			disable: i === selected
		}
	});
	//Splits
	var splits = [];
	if (pageCount <= 1) {
		//Noop
	} else if (pageCount < 11) {
		splits.push([0, pageCount]);
	} else if (selected < 5) {
		splits.push([0, 7]);
		splits.push([pageCount - 3, pageCount]);
	} else if (pageCount - selected < 6) {
		splits.push([0, 3]);
		splits.push([pageCount - 7, pageCount]);
	} else {
		splits.push([0, 2]);
		splits.push([selected - 2, selected + 3]);
		splits.push([pageCount - 2, pageCount]);
	}
	var pages = [];
	splits.forEach(function(split) {
		for (var i = split[0]; i < split[1]; i ++) {
			pages.push(pageList[i]);
		}
		pages.push({
			display: "...",
			disable: true
		})
	});
	pages.splice(pages.length - 1, 1);

	pag.append(Twig.twig({ref: "Pagination"}).render({
		pages: pages,
		selected: selected,
		pageCount: pageCount
	}));
}

function generateList(page) {
	//Clear everything first

	var list = $("#list");
	//Cancel all previous image loads
	list.find("img").attr("src", "");
	//https://stackoverflow.com/a/1468452/214063
	if (window.stop !== undefined) {
		window.stop();
	} else if(document.execCommand !== undefined) {
		document.execCommand("Stop", false);
	}
	list.empty();

	//And regenerate
	generatePagination(page);

	var displayList = filteredList.slice(page * pageSize, (page + 1) * pageSize);
	list.append(Twig.twig({ref: "MissionList"}).render({
		config: config,
		missions: displayList,
		difficulties: difficulties
	}));
}

function selectMission(missionId) {
	var tr = findMissionRowById(missionId);
	tr.parent().children(".selected").removeClass("selected");
	tr.addClass("selected");

	var mission = findMissionById(missionId);

	var info = $("#infoPanel");
	info.empty();
	info.append(Twig.twig({ref: "Selection"}).render({
		mission: mission,
		config: config
	}));
}

function setSortType(type) {
	if (sort.type === type) {
		sort.direction *= -1;
	} else {
		sort.type = type;
		sort.direction = 1;
	}

	filterList(lastQuery);
	generateList(0);
}

function filterList(search) {
	search = search.toLowerCase();
	lastQuery = search;

	//Do the user's search query
	filteredList = missionList.filter(function(mission) {
		if (mission.name.toLowerCase().indexOf(search) !== -1)
			return true;
		if (mission.desc.toLowerCase().indexOf(search) !== -1)
			return true;
		if (mission.artist.toLowerCase().indexOf(search) !== -1)
			return true;
		return false;
	});
	//Get cleaned up sort names for each mission
	filteredList = filteredList.map(function(mission) {
		mission.sortName = mission.name.toLowerCase().trim();
		return mission;
	});
	//Then sort by them
	filteredList.sort(function(a, b) {
		if (a[sort.type] === null) {
			return 1;
		}
		if (b[sort.type] === null) {
			return -1;
		}

		return sorts[sort.type](a, b, sort.direction);
	});

	if (filterTimeout != 0)
		clearTimeout(filterTimeout);

	filterTimeout = setTimeout(function() {
		generateList(0);
		filterTimeout = 0;
		if (search !== "") {
			window.location.hash = "q=" + encodeURIComponent(search);
		}
	}, 500);
}

function rateMission(missionId, direction) {
	$.ajax({
		method: "POST",
		url: config.base + "/api/v1/missions/" + missionId + "/rate",
		dataType: "json",
		data: {
			"direction": direction
		}
	}).done(function(data) {
		var mission = findMissionById(missionId);
		Object.assign(mission, data);
		var tr = findMissionRowById(missionId);
		tr.replaceWith($(Twig.twig({ref: "Mission"}).render({
			mission: mission,
			config: config}
		)).addClass("selected"));
	});
}

function updateMission(missionId) {
	var $form = $("#updateForm");
	var $inputs = $form.find("input");

	//Build url params
	var params = {
		id: missionId,
		fields: {}
	};
	for (var i = 0; i < $inputs.length; i ++) {
		var $input = $($inputs[i]);

		var name = $input.attr("name");
		var value = $input.val();

		if (typeof(name) === 'undefined') {
			continue;
		}

		if (name === "key") {
			params[name] = value;
		} else {
			params.fields[name] = value;
		}
	}

	$.ajax({
		method: "POST",
		url: config.base + "/api/v1/missions/" + missionId + "/update",
		dataType: "json",
		data: params
	}).done(function(data) {
		alert("yep");
	}).catch(function(reason) {
		alert("nope");
	});
}

function getMissionList() {
	mainScreen.hide();
	loadScreen.show();

	$.ajax({
		method: "GET",
		url: config.base + "/api/v1/missions/all",
		dataType: "json",
		data: {}
	}).done(function(data) {
		mainScreen.show();
		loadScreen.hide();

		var searchBar = $("#searchBar");
		searchBar.focus();
		searchBar.keyup(function() {
			var $this = $(this);
			var search = $this.val();
			filterList(search);
		});

		missionList = filteredList = data;

		var search = "";
		if (window.location.hash) {
			var hash = window.location.hash;
			var re = new RegExp("q=(.*?)(&|#|$)", "i");
			var matches = hash.match(re);
			if (matches.length > 0) {
				search = decodeURIComponent(matches[1]);
				searchBar.val(search);
			}
		}

		filterList(search);
		generateList(0);
	}).progress(function(e) {
		if (e.lengthComputable) {
			var percentage = Math.round((e.loaded * 100) / e.total);
			$("#loadingProgress").width(percentage + "%");
		}
	});
}


loadTemplates([
	{id: 'MissionList', href: '/assets/templates/MissionList.twig'},
	{id: 'Mission', href: '/assets/templates/Mission.twig'},
	{id: 'Pagination', href: "/assets/templates/Pagination.twig"},
	{id: 'Selection', href: "/assets/templates/edit/Selection.twig"},
]).then(getMissionList);
