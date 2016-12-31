<html>
<head>
	<title>Wow Such List</title>
	<style>
		#list {
			display: flex;
			flex-wrap: wrap;
			flex-direction: row;
			justify-content: center;
		}
		#list .mission {
			width: 240px;
			min-height: 160px;
			border: 1px solid #999;
			border-radius: 10px;
			margin: 10px;
			display: flex;
			flex-direction: column;
		}
		#list .mission .image {
			width: 200px;
			height: 127px;
			align-self: center;
		}
		#list .mission .title {
			text-align: center;
		}
	</style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
</head>
<body>
	<div id="pagination">
	</div>
	<div id="list">
	</div>
    <script type="text/javascript">
function generateList(missions) {
    var pag = $("#pagination");
    pag.empty();
    var pages = Math.ceil(missions.total / missions.pageSize);
    for (var i = 0; i < pages; i ++) {
        pag.append([
            $("<a>")
                .click((function(i) {
                    return function(event) {
                        getPage(i);
                    }
                })(i))
                .text(i + 1)
                .attr("href", "javascript:void(0)"),
            " "
        ])
    }

    var list = $("#list");
    list.empty();
    missions.missions.forEach(function(mission) {
        list.append(
            $("<div>")
                .addClass("mission")
                .append([
                    $("<img>")
                        .attr("src", mission.bitmapURL)
                        .addClass("image"),
                    $("<div>")
                        .text(mission.name)
                        .addClass("title"),
                    $("<div>")
                        .text('"' + mission.desc + '"')
                        .addClass("desc"),
                    $("<div>")
                        .text("By " + mission.artist)
                        .addClass("artist"),
                    $("<div>")
                        .text("Mod (Probably): " + mission.modification)
                        .addClass("modification"),
                    $("<div>")
                        .text("Gem Count: " + mission.gems)
                        .addClass("gems"),
                    $("<div>")
                        .text("Has Easter Egg: " + (mission.egg ? "Yes" : "No"))
                        .addClass("easteregg"),
                    $("<div>")
                        .addClass("download")
                        .append(
                            $("<a>")
                                .attr("href", mission.downloadURL)
                                .text("Download (WIP)")
                        )
                ])
        )
    });
}

function getPage(page) {
    $.ajax({
        method: "GET",
        url: "/api/getMissionList.php",
        dataType: "json",
        data: {"page": page}
    }).done(function (data) {
        generateList(data);
    });
}
getPage(0);
    </script>
</body>
</html>
