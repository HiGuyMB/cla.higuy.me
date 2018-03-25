var missionList = [];
var pageSize = 20;

var filteredList = [];
var filterTimeout = 0;

var loadScreen = $("#loadScreen");
var mainScreen = $("#mainScreen");

Handlebars.registerHelper('boolean', function(bool, options) {
	return bool ? "Yes" : "No";
});

Handlebars.registerHelper('each-first', function(pages, options) {
	return options.fn(pages[pages.length - 1]);
});
Handlebars.registerHelper('each-center', function(pages, options) {
	var ret = "";
	for (var i = 1, j = pages.length - 1; i < j; i ++) {
		ret = ret + options.fn(pages[i]);
	}
	return ret;
});

Handlebars.registerHelper('each-first', function(pages, options) {
	return options.fn(pages[pages.length - 1]);
});

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

	var template = Handlebars.compile($("#paginationTemplate").html());
	pag.append(template({pages: pages}));
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
	var template = Handlebars.compile($("#listTemplate").html());
	list.append(template({
		config: config,
		missions: displayList
	}));

	list.find(".mission").click(function(e) {
		//Ignore clicking link
		if (e.target.tagName.toLowerCase() === "a") {
			return;
		}

		$(this).toggleClass("expanded");
	})
}

function filterList(search) {
	search = search.toLowerCase();

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
		return a.sortName.localeCompare(b.sortName);
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

function getMissionList() {
	mainScreen.hide();
	loadScreen.show();

	$.ajax({
		method: "GET",
		url: config.base + "/api/getMissionList.php",
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
getMissionList();
