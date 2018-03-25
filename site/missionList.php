<html>
<head>
	<title>CLA Mission List</title>
	<!-- jQuery -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>

	<!-- Bootstrap -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <!-- Handlebars -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.10/handlebars.min.js"></script>

    <script type="text/javascript">
        config = {
            "base": location.origin + location.pathname.substr(0, location.pathname.lastIndexOf("/"))
        }
	</script>

    <!-- Mobile -->
    <meta name="viewport" content="width=500, maximum-scale=1">

	<link rel="stylesheet" href="assets/missionList.css">
	<script src="assets/jq-ajax-progress.min.js"></script>
	<script src="assets/range.js"></script>
</head>
<body>
<div id="mainScreen" class="row">
	<div id="optionsPanel" class="col-md-2">
		<h3>Search Options</h3>
		<ul>
			<li>Todo</li>
			<li>Stuff</li>
			<li>Here</li>
		</ul>
	</div>
	<div id="listPanel" class="col-md-10">
		<div id="search">
			<form action="javascript:void(0);">
				<input type="text" placeholder="Search" id="searchBar" class="form-control">
			</form>
		</div>
        <nav>
            <ul class="pagination" id="pageSelector"></ul>
        </nav>
		<div id="list"></div>
        <script id="listTemplate" type="text/x-handlebars-template">
            {{#each missions}}
                <div class="mission">
                    <img src="{{../config.base}}/api/getMissionBitmap.php?id={{id}}" alt="" class="image">
                    <div class="title">{{name}}</div>
                    <div class="info">
                        <div class="metadata">
                            <div class="artist">By {{artist}}</div>
                            <div class="modification">Mod: {{modification}}</div>
                            <div class="gems">Gems: {{gems}}</div>
                            <div class="easteregg">Egg: {{boolean egg}}</div>
                        </div>
                        <div class="desc">{{desc}}</div>
                    </div>
                    <div class="download">
                        <a href="{{../config.base}}/api/getMissionFiles.php?id={{id}}">Files</a>
                        <a href="{{../config.base}}/api/getMissionZip.php?id={{id}}">Download</a>
                    </div>
                </div>
            {{/each}}
        </script>
        <script id="paginationTemplate" type="text/x-handlebars-template">
            {{#each pages}}
            {{#if disable}}
                <li class="disabled">
                    <a href="javascript:void(0)">{{display}}</a>
                </li>
            {{else}}
                <li>
                    <a href="javascript:void(0)" onclick="generateList({{index}})">{{display}}</a>
                </li>
                {{/if}}
            {{/each}}
        </script>
	</div>
</div>
<div id="loadScreen">
	<div class="progress">
		<div class="progress-bar" style="width: 0" id="loadingProgress"></div>
	</div>
</div>
<script type="application/javascript" src="assets/missionList.js"></script>
</body>
</html>
