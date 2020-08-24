var range = function(start, end, step) {
	var range = [];
	var typeofStart = typeof start;
	var typeofEnd = typeof end;

	if (step === 0) {
		throw TypeError("Step cannot be zero.");
	}

	if (typeofStart === "undefined" || typeofEnd === "undefined") {
		throw TypeError("Must pass start and end arguments.");
	} else if (typeofStart !== typeofEnd) {
		throw TypeError("Start and end arguments must be of same type.");
	}

	typeof step === "undefined" && (step = 1);

	if (end < start) {
		step = -step;
	}

	if (typeofStart === "number") {

		while (step > 0 ? end >= start : end <= start) {
			range.push(start);
			start += step;
		}

	} else if (typeofStart === "string") {

		if (start.length !== 1 || end.length !== 1) {
			throw TypeError("Only strings with one character are supported.");
		}

		start = start.charCodeAt(0);
		end = end.charCodeAt(0);

		while (step > 0 ? end >= start : end <= start) {
			range.push(String.fromCharCode(start));
			start += step;
		}

	} else {
		throw TypeError("Only string and number types are supported");
	}

	return range;
};


//https://gist.github.com/andrei-m/982927
function levenshtein(a, b){
	var tmp;
	if (a.length === 0) { return b.length; }
	if (b.length === 0) { return a.length; }
	if (a.length > b.length) { tmp = a; a = b; b = tmp; }

	var i, j, res, alen = a.length, blen = b.length, row = new Array(alen);
	for (i = 0; i <= alen; i++) { row[i] = i; }

	for (i = 1; i <= blen; i++) {
		res = i;
		for (j = 1; j <= alen; j++) {
			tmp = row[j - 1];
			row[j - 1] = res;
			res = b[i - 1] === a[j - 1] ? tmp : Math.min(tmp + 1, Math.min(res + 1, row[j] + 1));
		}
	}
	return res;
}


/**
 * Asynchronously load all of the templates
 * Thanks, Kevin
 * @param {array} templates
 * @return {Promise}
 */
function loadTemplates(templates) {
	var promises = [];
	templates.forEach(function (template) {
		promises.push(new Promise(function (resolve, reject) {
			Twig.twig({
				id: template.id,
				href: template.href,
				allowInlineIncludes: true,
				async: true,
				error: function () {
					reject();
				},
				load: function () {
					resolve();
				}
			});
		}));
	});
	return Promise.all(promises);
}
