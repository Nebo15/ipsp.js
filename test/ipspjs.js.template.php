<?php

return <<<_JS
(function (g) {
	var H = g.CC_NUMBER = "number",
		V = g.CC_EXP_MONTH = "exp_month",
		e = g.CC_EXP_YEAR = "exp_year",
		B = g.CC_HOLDER = "cardholder",
		P = g.CC_CVC = "cvc",
		Z = g.CC_AMOUNT = "amount",
		Q = g.CC_AMOUNT_INT = "amount_int",
		z = g.CC_CURRENCY = "currency",
		j = g.DD_NUMBER = "number",
		G = g.DD_BANK = "bank",
		d = g.DD_HOLDER = "accountholder",
		ad = g.DD_COUNTRY = "country",
		s = g.DD_BIC = "bic",
		E = g.DD_IBAN = "iban",
		ACT = g.NF_ACTION = "action",
		BCID = g.NF_BILLER_CLIENT_ID = "biller_client_id",
		PEM = g.NF_PERSPAYEE_EXPIRY_MONTH = "perspayee_expiry_month",
		PEY = g.NF_PERSPAYEE_EXPIRY_TEAR = "perspayee_expiry_year",
		RF = g.NF_RECUR_FREQ = "recur_freq",
		w = g.E_CC_INVALID_NUMBER = "field_invalid_card_number",
		c = g.E_CC_INVALID_EXPIRY = "field_invalid_card_exp",
		ac = g.E_CC_INVALID_EXP_MONTH = "field_invalid_card_exp_month",
		p = g.E_CC_INVALID_EXP_YEAR = "field_invalid_card_exp_year",
		an = g.E_CC_INVALID_CVC = "field_invalid_card_cvc",
		q = g.E_CC_INVALID_HOLDER = "field_invalid_card_holder",
		C = g.E_CC_INVALID_AMOUNT = "field_invalid_amount",
		R = g.E_CC_INVALID_AMOUNT_INT = "field_invalid_amount_int",
		S = g.E_CC_INVALID_CURRENCY = "field_invalid_currency",
		m = g.E_DD_INVALID_NUMBER = "field_invalid_account_number",
		al = g.E_DD_INVALID_BANK = "field_invalid_bank_code",
		f = g.E_DD_INVALID_HOLDER = "field_invalid_account_holder",
		ap = g.E_DD_INVALID_BANK_DATA = "field_invalid_bank_data",
		ah = g.E_DD_INVALID_IBAN = "field_invalid_iban",
		N = g.E_DD_INVALID_COUNTRY = "field_invalid_country",
		W = g.E_DD_INVALID_BIC = "field_invalid_bic",
		ae = g.DEBIT_TYPE_ELV = "elv",
		ab = g.DEBIT_TYPE_SEPA = "sepa";
	var T = {};
	g.config = function T(ar, at) {
		if (at !== undefined) {
			T[ar] = at
		}
		return T[ar]
	};
	var K = g.increaseMonetaryUnit = function K(au, at, ar) {
		at = at ? at : 100;
		ar = ar ? ar : 2;
		au = (au / at).toFixed(ar);
		return au
	};
	if (!Array.prototype.indexOf) {
		Array.prototype.indexOf = function (au) {
			if (this == null) {
				throw new TypeError()
			}
			var av = Object(this);
			var ar = av.length >>> 0;
			if (ar === 0) {
				return -1
			}
			var aw = 0;
			if (arguments.length > 1) {
				aw = Number(arguments[1]);
				if (aw != aw) {
					aw = 0
				} else {
					if (aw != 0 && aw != Infinity && aw != -Infinity) {
						aw = (aw > 0 || -1) * Math.floor(Math.abs(aw))
					}
				}
			}
			if (aw >= ar) {
				return -1
			}
			var at = aw >= 0 ? aw : Math.max(ar - Math.abs(aw), 0);
			for (; at < ar; at++) {
				if (at in av && av[at] === au) {
					return at
				}
			}
			return -1
		}
	}
	var l = {
		"4012888888881881": true,
		"5169147129584558": true
	};
	var M = [{
		type: "American Express",
		pattern: /^3[47]/,
		luhn: true,
		cvcLength: [3, 4],
		numLength: [15]
	}, {
		type: "Discover",
		pattern: /^(6011|622(1[2-90][6-9]|[2-8]\d{2}|9[0-1]\d|92[0-5])|64[4-9]|65)/,
		luhn: true,
		cvcLength: [3],
		numLength: [16]
	}, {
		type: "UnionPay",
		pattern: /^62/,
		luhn: false,
		cvcLength: [3],
		numLength: [16, 17, 18, 19]
	}, {
		type: "Diners Club",
		pattern: /^(30[0-5]|36|38)/,
		luhn: true,
		cvcLength: [3],
		numLength: [14]
	}, {
		type: "JCB",
		pattern: /^35([3-8][0-9]|2[8-9])/,
		luhn: true,
		cvcLength: [3],
		numLength: [16]
	}, {
		type: "Maestro",
		pattern: /^(5018|5020|5038|5893|6304|6331|6703|6759|676[1-3]|6799|0604)/,
		luhn: true,
		cvcLength: [0, 3, 4],
		numLength: [12, 13, 14, 15, 16, 17, 18, 19]
	}, {
		type: "MasterCard",
		pattern: /^(5[1-5])/,
		luhn: true,
		cvcLength: [3],
		numLength: [16]
	}, {
		type: "Visa",
		pattern: /^4/,
		luhn: true,
		cvcLength: [3],
		numLength: [13, 16]
	}];
	var am = g.tr = function am(ar) {
		return ((ar || "") + "").replace(/^\s+|\s+$/g, "")
	};
	var h = g.clr = function h(ar) {
		return (ar + "").replace(/\s+|-/g, "")
	};
	var ak = g.flip = function ak(ar) {
		return (ar + "").split("").reverse().join("")
	};
	var aj = g.chksum = function aj(ax) {
		if (ax.match(/^\d+$/) === null) {
			return false
		}
		var aw = ak(ax);
		var au = aw.length;
		var ar;
		var at = 0;
		var av;
		for (ar = 0; ar < au; ++ar) {
			av = parseInt(aw.charAt(ar), 10);
			if (0 !== ar % 2) {
				av *= 2
			}
			at += (av < 10) ? av : av - 9
		}
		return (0 !== at && 0 === at % 10)
	};
	var b = g.toFormEncoding = function b(av, au) {
		var aw = [];
		for (var ax in av) {
			if (av.hasOwnProperty(ax)) {
				var ar = au ? au + "[" + ax + "]" : ax;
				var at = av[ax];
				aw.push("object" === typeof at ? b(at, ar) : encodeURIComponent(ar) + "=" + encodeURIComponent(at))
			}
		}
		return aw.join("&")
	};

	function af(av) {
		av = h(av);
		var at, au, ar;
		for (au = 0, ar = M.length; au < ar; au++) {
			at = M[au];
			if (at.pattern.test(av)) {
				return at
			}
		}
	}
	var F = g.validateCardNumber = function F(at) {
		at = h(at);
		var ar = af(at);
		if (!at || !ar) {
			return false
		}
		if (ar.luhn && false == aj(at)) {
			return false
		}
		return ar.numLength.indexOf(at.length) != -1
	};
	var J = g.validateCvc = function J(av, aw) {
		av = am(av);
		if (!aw) {
			return null !== av.match(/^\d{3,4}$/)
		}
		aw = h(aw);
		var at, au, ar;
		for (au = 0, ar = M.length; au < ar; au++) {
			at = M[au];
			if (at.pattern.test(aw)) {
				if (av.length > 0) {
					return at.cvcLength.indexOf(av.length) != -1 && null !== av.match(/^\d+$/)
				} else {
					return at.cvcLength.indexOf(av.length) != -1
				}
			}
		}
		return false
	};
	var Y = g.validateExpMonth = function Y(ar) {
		return /^([1-9]|0[1-9]|1[012])$/.test(am(ar))
	};
	var aa = g.validateExpYear = function aa(ar) {
		return /^\d{4}$/.test(am(ar))
	};
	var I = g.validateExpiry = function I(aw, au) {
		if (!Y(aw) || !aa(au)) {
			return false
		}
		aw = parseInt(am(aw), 10);
		au = parseInt(am(au), 10);
		var av = new Date(),
			ar = av.getFullYear(),
			at = av.getMonth() + 1;
		return au > ar || (au === ar && aw >= at)
	};
	var k = g.validateAmount = function k(ar) {
		ar = am(ar);
		return /^([0-9]+)(\.[0-9]+)?$/.test(ar)
	};
	var O = g.validateAmountInt = function O(ar) {
		ar = am(ar);
		return /^[0-9]+$/.test(ar)
	};
	var X = g.validateCurrency = function X(ar) {
		ar = am(ar);
		return /^[A-Z]{3}$/.test(ar)
	};
	var ag = g.validateHolder = function ag(ar) {
		if (!ar) {
			return false
		}
		return /^.{4,128}$/.test(am(ar))
	};
	var D = g.validateAccountNumber = function D(ar) {
		return /^\d+$/.test(h(ar))
	};
	var r = g.validateBankCode = function r(ar) {
		return /^\d{8}$/.test(h(ar))
	};
	var u = g.cardType = function u(au) {
		var at;
		if (F(au)) {
			at = af(au), ar
		}
		var ar = at ? at.type : "Unknown";
		return ar
	};
	var n = g.getApiKey = function n() {
		if (typeof IPSPJS_PUBLIC_KEY === "undefined") {
			throw new Error("No public api key is set. You need to set the global IPSPJS_PUBLIC_KEY variable to your public api key in order to use this api.")
		}
		return IPSPJS_PUBLIC_KEY
	};
	var v = g.isTestKey = function v(ar) {
		return (ar + "").match(/^\d{10}/) || (typeof IPSPJS_TEST_MODE !== "undefined" && IPSPJS_TEST_MODE === true)
	};
	g.transport = {
		execute: function U(at, ar, au) {
			throw new Error("ipspjs.transport.execute() not implemented. Wtf?")
		}
	};
	var a = g.createToken = function a(aw, ay, ar, av) {
		var ax = n(),
			au = {
				type: "createToken"
			};
		try {
			au.data = (aw[G] === undefined && aw[s] === undefined) ? x(aw, ax) : ao(aw);
			g.transport.execute(ax, au, ay, ar, av)
		} catch (at) {
			setTimeout(function () {
				ay({
					apierror: at
				})
			}, 0)
		}
	};

	function ai(au, ar) {
		var at = new XMLHttpRequest();
		if ("withCredentials" in at) {
			at.open(au, ar, true)
		} else {
			if (typeof XDomainRequest != "undefined") {
				at = new XDomainRequest();
				at.open(au, ar)
			} else {
				at = null
			}
		}
		return at
	}
	var y = g.getBankName = function y(aw, ax) {
		if (aw == "") {
			return
		}
		var av = "";
		try {
			av = t(aw)
		} catch (ar) {
			ax({
				apierror: ar
			});
			return
		}
		if (typeof JSON !== "object") {
			setTimeout(function () {
				ax({
					apierror: "Woops, there was an error creating the request."
				})
			}, 0);
			return
		}
		var at = g.getBlzUrl(av);
		var au = ai("GET", at);
		if (!au) {
			setTimeout(function () {
				ax({
					apierror: "Woops, there was an error creating the request."
				})
			}, 0);
			return
		}
		au.onload = function () {
			var az = au.responseText;
			var ay = JSON.parse(az).data;
			if (typeof ay.success !== "undefined") {
				if (ay.success) {
					ax(null, ay.name)
				} else {
					ax({
						apierror: ay.error
					})
				}
			} else {
				ax({
					apierror: "Woops, there was an error extracting the request."
				})
			}
		};
		au.onerror = function () {
			ax({
				apierror: "Woops, there was an error making the request."
			})
		};
		au.send()
	};

	function t(at) {
		if (/\D/.test(at)) {
			var ar = at.toString();
			if (ar.length == 8) {
				return ar + "XXX"
			} else {
				if (ar.length == 11) {
					return ar
				} else {
					if (o(ar)) {
						return ar.substr(4, 8)
					} else {
						throw ah
					}
				}
			}
		} else {
			if (at.toString().length != 8) {
				throw al
			}
			return at.toString()
		}
	}
	var A = g.getBlzUrl = function A(ar) {
		return "%IPSPJS_HOST_NAME%/?data=" + ar
	};

	function L(at, ar) {
		return (g.isTestKey(at) && l[ar] !== true)
	}

	function x(au, at) {
		var ar = {};
		ar[H] = h(au[H]);
		ar[V] = am(au[V]);
		ar[e] = am(au[e]);
		ar[P] = am(au[P]);
		ar[B] = am(au[B]);
		ar[Z] = am(au[Z]);
		ar[Q] = am(au[Q]);
		ar[z] = am(au[z]);
		ar[ACT] = am(au[ACT]);
		ar[BCID] = am(au[BCID]);
		ar[PEM] = am(au[PEM]);
		ar[PEY] = am(au[PEY]);
		ar[RF] = am(au[RF]);
		ar[V] = ("0" + ar[V]).slice(-2);
		if (!F(ar[H])) {
			throw w
		}
		if (!I(ar[V], ar[e])) {
			throw c
		}
		if (!J(ar[P], ar[H])) {
			throw an
		}
		if (ar[B] === undefined) {
			delete ar[B]
		}
		var av = L(at, ar[H]);
		if (O(ar[Q])) {
			ar[Z] = K(ar[Q]);
			delete ar[Q]
		} else {
			if (ar[Q] !== undefined && ar[Q] !== "") {
				throw R
			}
		} if (k(ar[Z])) {
			ar[Z] = K(ar[Z], 1, 2)
		} else {
			if (ar[Z] !== undefined && ar[Z] !== "") {
				throw C
			}
		} if (ar[z] !== undefined && ar[z] !== "" && !X(ar[z])) {
			throw S
		}
		if ((ar[Z] === undefined || ar[Z] === "") && (ar[z] !== undefined && ar[z] !== "")) {
			throw C
		} else {
			if ((ar[Z] !== undefined && ar[Z] !== "") && (ar[z] === undefined || ar[z] === "")) {
				throw S
			}
		}
		return ar
	}

	function ao(au) {
		var at = {};
		var ar = i(au);
		if (ar == ab) {
			at[E] = h(au[E]);
			at[s] = h(au[s]);
			if (!o(at[E])) {
				throw ah
			}
			if (!aq(at[s])) {
				throw W
			}
			at[ad] = au[E].substr(0, 2)
		} else {
			if (ar == ae) {
				at[j] = h(au[j]);
				at[G] = h(au[G]);
				if (!D(at[j])) {
					throw m
				}
				if (!r(at[G])) {
					throw al
				}
				at[ad] = "DE"
			} else {
				throw ap
			}
		}
		at[d] = am(au[d]);
		if (at[d] === undefined || at[d] === "") {
			throw f
		}
		if (!ag(at[d])) {
			throw f
		}
		return at
	}

	function i(at) {
		var ar = "unknown";
		if ((at[E] !== undefined) && (at[s] !== undefined)) {
			ar = ab
		} else {
			if ((at[G] !== undefined) && (at[j] !== undefined)) {
				ar = ae
			}
		}
		return ar
	}
	var o = g.validateIban = function o(ar) {
		ar = h(ar);
		var at = ar.substr(0, 2);
		if (at !== "DE") {
			throw N
		}
		if (at == "DE") {
			return /^DE\d{20}$/.test(ar)
		}
	};
	var aq = g.validateBic = function aq(ar) {
		ar = h(ar);
		return /[A-Z]{4}(DE)[A-Z1-9]{2}([A-Z\d]{3})?/.test(ar)
	}
})(window.ipspjs = {});
(function (b, d, a) {
	if (b === undefined || b == null) {
		throw new Error("ipspjs object not initialized")
	}
	b.getDeviceIdent = function c() {
		di = {
			v: "ipspjs-com"
		};
		(function () {
			var f = a.createElement("script");
			f.type = "text/javascript";
			f.async = true;
			f.src = "https://showcase.deviceident.com/pmcom/di-js.js";
			var e = a.getElementsByTagName("script")[0];
			e.parentNode.insertBefore(f, e)
		})()
	}
})(window.ipspjs, window, document);
(function (a) {
	a.dom = {
		css: function (c, b) {
			for (var d in b) {
				val = b[d];
				if (typeof val === "number") {
					val += "px"
				}
				c.style[d] = val
			}
		},
		computedStyle: function (c, d) {
			var b = "";
			if (document.defaultView && document.defaultView.getComputedStyle) {
				b = document.defaultView.getComputedStyle(c, "").getPropertyValue(d)
			} else {
				if (c.currentStyle) {
					d = d.replace(/\-(\w)/g, function (e, f) {
						return f.toUpperCase()
					});
					b = c.currentStyle[d]
				}
			}
			return b
		},
		bind: function (c, b, d) {
			if (c.addEventListener) {
				c.addEventListener(b, d, false)
			} else {
				if (c.attachEvent) {
					c.attachEvent("on" + b, d)
				}
			}
		},
		innerWidth: function () {
			if (typeof window.innerWidth === "number") {
				return window.innerWidth
			}
			if (window.documentElement && typeof window.documentElement.clientWidth === "number") {
				return window.documentElement.clientWidth
			}
			if (document.body && typeof document.body.clientWidth === "number") {
				return document.body.clientWidth
			}
		},
		innerHeight: function () {
			if (typeof window.innerHeight === "number") {
				return window.innerHeight
			}
			if (window.documentElement && typeof window.documentElement.clientHeight === "number") {
				return window.documentElement.clientHeight
			}
			if (document.body && typeof document.body.clientHeight === "number") {
				return document.body.clientHeight
			}
		}
	}
})(window.ipspjs === undefined ? window.ipspjs = {} : window.ipspjs);
(function (a, k, o) {
	if (a === undefined || a == null) {
		throw new Error("ipspjs object not initialized")
	}
	var f = o.head || o.getElementsByTagName("head")[0] || o.documentElement;
	var b = {
		test: "%IPSPJS_HOST_NAME%/",
		live: "%IPSPJS_HOST_NAME%/"
	};
	var q = {
		test: "%IPSPJS_HOST_NAME%/",
		live: "%IPSPJS_HOST_NAME%/"
	};
	var j = {
		test: "%IPSPJS_HOST_NAME%/",
		live: "%IPSPJS_HOST_NAME%/"
	};
	var c = "ACK",
		t = "NOK",
		p = "CONNECTOR_TEST",
		v = "LIVE";
	var e = {
		"100.100.600": a.E_CC_INVALID_CVC,
		"100.100.601": a.E_CC_INVALID_CVC,
		"100.100.303": a.E_CC_INVALID_EXPIRY,
		"100.100.304": a.E_CC_INVALID_EXPIRY,
		"100.100.301": a.E_CC_INVALID_EXP_YEAR,
		"100.100.300": a.E_CC_INVALID_EXP_YEAR,
		"100.100.201": a.E_CC_INVALID_EXP_MONTH,
		"100.100.200": a.E_CC_INVALID_EXP_MONTH,
		"100.100.100": [a.E_CC_INVALID_NUMBER, a.E_DD_INVALID_NUMBER],
		"100.100.101": [a.E_CC_INVALID_NUMBER, a.E_DD_INVALID_NUMBER],
		"100.100.400": [a.E_CC_INVALID_HOLDER, a.E_DD_INVALID_HOLDER],
		"100.100.401": [a.E_CC_INVALID_HOLDER, a.E_DD_INVALID_HOLDER],
		"100.100.402": [a.E_CC_INVALID_HOLDER, a.E_DD_INVALID_HOLDER],
		"600.200.500": "invalid_payment_data"
	};
	var h = {};
	a.transport.buildRequestUrl = function (A, z, y) {
		var w, x = a.toFormEncoding(z);
		if (y.bic !== undefined || y.iban !== undefined || y.bank !== undefined) {
			w = a.isTestKey(A) ? q.test : q.live
		} else {
			w = a.isTestKey(A) ? b.test : b.live
		} if (w.indexOf("?") >= 0) {
			w += "&" + x
		} else {
			w += "?" + x
		}
		return w
	};

	function s(A, F, E, x) {
		var D = null,
			z = null,
			y = null;
		if (A === null) {
			return F(u("internal_server_error"), null)
		} else {
			if (A.error) {
				if (/check channelId or login/.test(A.error.message)) {
					return F(u("invalid_public_key"))
				}
				return F(u("unknown_error", A.error.message || A.error))
			} else {
				var w = A.transaction.processing;
				if (w.result === c) {
					var y = A.transaction.identification.uniqueId,
						C = A.transaction.customer,
						B = A.transaction.account;
					if (C) {
						z = {
							token: y,
							bin: B.bin,
							binCountry: C.address.country,
							brand: B.brand,
							last4Digits: B.last4Digits,
							ip: C.contact.ip,
							ipCountry: C.contact.ipCountry
						}
					} else {
						z = {
							token: y
						}
					} if (w.redirect) {
						g(A, y, F, E, x)
					} else {
						return F(null, z)
					}
				} else {
					return F(m(A), null)
				}
			}
		}
	}
	var n = [];

	function r(F, A) {
		var x = F.url,
			P = F.params;
		var D = o.body || o.getElementsByTagName("body")[0];
		var L = o.createElement("div");
		L.style.cssText = "display: none";
		L.innerHTML = '<div></div><iframe><html><body></body></html></iframe><div><input type="submit"></div>';
		var B = L.firstChild.nextSibling.nextSibling.firstChild;
		var C = L.firstChild.nextSibling;
		a.dom.bind(B, "click", A);
		D.insertBefore(L, D.firstChild);
		n.push(L);
		var G = C.contentWindow || C.contentDocument;
		if (G.document) {
			G = G.document
		}
		var Form3ds = G.createElement("form");
		Form3ds.method = "post";
		Form3ds.action = x;
		for (var K1 in P) {
			var E1 = G.createElement("input");
			E1.type = "hidden";
			E1.name = K1;
			E1.value = decodeURIComponent(P[K1]);
			Form3ds.appendChild(E1)
		}
		L.appendChild(Form3ds);
		Form3ds.submit();
	}

	function d() {
		var w = n.length;
		while (w--) {
			if (n[w] && n[w].parentNode) {
				n[w].parentNode.removeChild(n[w])
			}
		}
		n.length = 0
	}

	function g(B, x, H, E, w) {
		var D = B.transaction.processing.redirect;
		var F = B.transaction.mode === p;
		var A = {
			url: decodeURIComponent(D.url),
			params: {}
		};
		for (var z in D.parameters) {
			A.params[z] = D.parameters[z]
		}
		var G = E || r,
			y = w || d,
			C = j[F ? "test" : "live"];
		G(A, function () {
			y();
			H(u("3ds_cancelled"))
		});
		a.receiveMessage();
		a.receiveMessage(function (J, I) {
			if (J.data === "ok") {
				y();
				H(null, {
					token: x
				})
			}
			if (J.data === "cancelled") {
				y();
				H(u("3ds_cancelled"))
			}
		}, C.replace(/([^:]+:\/\/[^\/]+).*/, "$1"))
	}

	function m(y) {
		var x = y.transaction.processing["return"].code,
			w = y.transaction.processing["return"].message;
		if (e[x] !== undefined) {
			var z = e[x];
			if (Object.prototype.toString.apply(z) === "[object Array]") {
				return u(z[0])
			}
			return u(z)
		}
		return u("unknown_error", w)
	}

	function u(x, w) {
		if (w === undefined) {
			return {
				apierror: x
			}
		}
		return {
			apierror: x,
			message: w
		}
	}
	var l = {
		action: "action",
		exp_month: "account.expiry.month",
		exp_year: "account.expiry.year",
		cardholder: "account.holder",
		number: "account.number",
		amount: "presentation.amount3D",
		currency: "presentation.currency3D",
		cvc: "account.verification",
		accountholder: "account.holder",
		bank: "account.bank",
		country: "account.country",
		iban: "account.iban",
		bic: "account.bic",
		biller_client_id: "recurring.biller.client.id",
		perspayee_expiry_month: "recurring.perspayee.expiry.month",
		perspayee_expiry_year: "recurring.perspayee.expiry.year",
		recur_freq: "recurring.recur.freq"
	};
	a.transport.execute = function i(B, A, G, F, x) {
		var D = "ipspjsCallback" + parseInt(Math.random() * 4294967295, 10);
		h[D] = null;
		a.transport[D] = function (I) {
			h[D] = I
		};
		var w = a.isTestKey(B),
			E = w ? p : v,
			H = j[w ? "test" : "live"];
		H += "?parentUrl=" + encodeURIComponent(encodeURIComponent(k.location.href)) + "&";
		var z = {
			"transaction.mode": E,
			"channel.id": B,
			"response.url": H,
			jsonPFunction: "window.ipspjs.transport." + D
		};
		for (var y in A.data) {
			if (l[y] === undefined) {
				continue
			}
			z[l[y]] = A.data[y]
		}
		var C = o.createElement("script");
		C.async = "async";
		C.src = a.transport.buildRequestUrl(B, z, A.data);
		C.onload = C.onerror = C.onreadystatechange = function (J) {
			if (!C.readyState || /loaded|complete/.test(C.readyState)) {
				var I = h[D];
				delete ipspjs.transport[D];
				delete h[D];
				C.onload = C.onerror = C.onreadystatechange = null;
				f.removeChild(C);
				s(I, G, F, x)
			}
		};
		f.insertBefore(C, f.firstChild)
	}
})(window.ipspjs, window, document);
(function (c) {
	var e, f, d = 1,
		b;
	c.postMessage = function a(h, j, i) {
		if (!j) {
			return
		}
		i = i || parent;
		if (window.postMessage) {
			i.postMessage(h, j.replace(/([^:]+:\/\/[^\/]+).*/, "$1"))
		} else {
			if (j) {
				i.location = j.replace(/#.*$/, "") + "#" + (+new Date) + (d++) + "&" + h
			}
		}
	};
	c.receiveMessage = function g(i, h) {
		if (window.postMessage) {
			if (i) {
				b = function (j) {
					if ((typeof h === "string" && j.origin !== h) || (Object.prototype.toString.call(h) === "[object Function]" && h(j.origin) === !1)) {
						return !1
					}
					i(j)
				}
			}
			if (window.addEventListener) {
				window[i ? "addEventListener" : "removeEventListener"]("message", b, !1)
			} else {
				if (b) {
					window[i ? "attachEvent" : "detachEvent"]("onmessage", b)
				}
			}
		} else {
			e && clearInterval(e);
			e = null;
			if (i) {
				e = setInterval(function () {
					var k = document.location.hash,
						j = /^#?\d+&/;
					if (k !== f && j.test(k)) {
						f = k;
						i({
							data: k.replace(j, "")
						})
					}
				}, 100)
			}
		}
	}
})(window.ipspjs === undefined ? window.ipspjs = {} : window.ipspjs);
_JS;
