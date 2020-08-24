var textures = null;
var uniques = null;

$.get(config.base + "/api/v1/textures").then(function(data) {
	textures = data.filter(function(tex) {
		//Ignore lb textures for now
		if (tex.gamePath.indexOf("/lbinteriors") !== -1) {
			return false;
		}
		//Ignore non-interiors
		if (tex.gamePath.indexOf("/missions") !== -1) {
			return false;
		}
		if (tex.gamePath.indexOf("/shapes") !== -1) {
			return false;
		}
		if (tex.gamePath.indexOf("/skies") !== -1) {
			return false;
		}
		if (tex.gamePath.indexOf("/multiplayer") !== -1) {
			if (tex.gamePath.indexOf("/interiors") === -1) {
				return false;
			}
		}
		//Empty
		if (tex.hash === null) {
			return false;
		}
		return true;
	}).map(function(tex) {
		tex.name = tex.baseName.substring(0, tex.baseName.lastIndexOf("."));
		tex.variants = [tex.gamePath];
		return tex;
	}).sort(function(a, b) {
		return a.gamePath.localeCompare(b.gamePath);
	});

	for (var i = 0; i < textures.length; i ++) {
		for (var j = 0; j < i; j ++) {
			if (textures[i].hash === textures[j].hash) {
				textures[j].variants.push(textures[i].gamePath);
				textures.splice(i, 1);
				i --;
				break;
			}
		}
	}
}).catch(function(err) {
	alert("Error downloading texture list: " + err);
}).then(function() {
	return loadTemplates([
		{id: 'TextureRow', href: 'assets/templates/TextureRow.twig'},
	]);
}).then(function() {
	$("#search").autocomplete({
		delay: 500,
		source: function(request, response) {
			var term = request.term;
			if (textures === null) {
				response([]);
			} else {
				response(fuzzysort.go(term, textures, {key: "gamePath"}).slice(0, 20));
			}
		}
	}).data('ui-autocomplete')._renderItem = function(ul, item) {
		var li = $(Twig.twig({ref: "TextureRow"}).render({
			tex: item.obj,
			title: fuzzysort.highlight(item, '<strong>', '</strong>')
		}));
		return li.appendTo(ul);
	};
});