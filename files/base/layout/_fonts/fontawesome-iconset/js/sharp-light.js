(function () {
  'use strict';

  function ownKeys(object, enumerableOnly) {
    var keys = Object.keys(object);

    if (Object.getOwnPropertySymbols) {
      var symbols = Object.getOwnPropertySymbols(object);
      enumerableOnly && (symbols = symbols.filter(function (sym) {
        return Object.getOwnPropertyDescriptor(object, sym).enumerable;
      })), keys.push.apply(keys, symbols);
    }

    return keys;
  }

  function _objectSpread2(target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = null != arguments[i] ? arguments[i] : {};
      i % 2 ? ownKeys(Object(source), !0).forEach(function (key) {
        _defineProperty(target, key, source[key]);
      }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }

    return target;
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _toConsumableArray(arr) {
    return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
  }

  function _arrayWithoutHoles(arr) {
    if (Array.isArray(arr)) return _arrayLikeToArray(arr);
  }

  function _iterableToArray(iter) {
    if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
  }

  function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
  }

  function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;

    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

    return arr2;
  }

  function _nonIterableSpread() {
    throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  function _createForOfIteratorHelper(o, allowArrayLike) {
    var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];

    if (!it) {
      if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
        if (it) o = it;
        var i = 0;

        var F = function () {};

        return {
          s: F,
          n: function () {
            if (i >= o.length) return {
              done: true
            };
            return {
              done: false,
              value: o[i++]
            };
          },
          e: function (e) {
            throw e;
          },
          f: F
        };
      }

      throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }

    var normalCompletion = true,
        didErr = false,
        err;
    return {
      s: function () {
        it = it.call(o);
      },
      n: function () {
        var step = it.next();
        normalCompletion = step.done;
        return step;
      },
      e: function (e) {
        didErr = true;
        err = e;
      },
      f: function () {
        try {
          if (!normalCompletion && it.return != null) it.return();
        } finally {
          if (didErr) throw err;
        }
      }
    };
  }

  var _WINDOW = {};
  var _DOCUMENT = {};

  try {
    if (typeof window !== 'undefined') _WINDOW = window;
    if (typeof document !== 'undefined') _DOCUMENT = document;
  } catch (e) {}

  var _ref = _WINDOW.navigator || {},
      _ref$userAgent = _ref.userAgent,
      userAgent = _ref$userAgent === void 0 ? '' : _ref$userAgent;
  var WINDOW = _WINDOW;
  var DOCUMENT = _DOCUMENT;
  var IS_BROWSER = !!WINDOW.document;
  var IS_DOM = !!DOCUMENT.documentElement && !!DOCUMENT.head && typeof DOCUMENT.addEventListener === 'function' && typeof DOCUMENT.createElement === 'function';
  var IS_IE = ~userAgent.indexOf('MSIE') || ~userAgent.indexOf('Trident/');

  var _familyProxy, _familyProxy2, _familyProxy3, _familyProxy4, _familyProxy5;

  var NAMESPACE_IDENTIFIER = '___FONT_AWESOME___';
  var PRODUCTION = function () {
    try {
      return "production" === 'production';
    } catch (e) {
      return false;
    }
  }();
  var FAMILY_CLASSIC = 'classic';
  var FAMILY_SHARP = 'sharp';
  var FAMILIES = [FAMILY_CLASSIC, FAMILY_SHARP];

  function familyProxy(obj) {
    // Defaults to the classic family if family is not available
    return new Proxy(obj, {
      get: function get(target, prop) {
        return prop in target ? target[prop] : target[FAMILY_CLASSIC];
      }
    });
  }
  var PREFIX_TO_STYLE = familyProxy((_familyProxy = {}, _defineProperty(_familyProxy, FAMILY_CLASSIC, {
    'fa': 'solid',
    'fas': 'solid',
    'fa-solid': 'solid',
    'far': 'regular',
    'fa-regular': 'regular',
    'fal': 'light',
    'fa-light': 'light',
    'fat': 'thin',
    'fa-thin': 'thin',
    'fad': 'duotone',
    'fa-duotone': 'duotone',
    'fab': 'brands',
    'fa-brands': 'brands',
    'fak': 'kit',
    'fa-kit': 'kit'
  }), _defineProperty(_familyProxy, FAMILY_SHARP, {
    'fa': 'solid',
    'fass': 'solid',
    'fa-solid': 'solid',
    'fasr': 'regular',
    'fa-regular': 'regular',
    'fasl': 'light',
    'fa-light': 'light'
  }), _familyProxy));
  var STYLE_TO_PREFIX = familyProxy((_familyProxy2 = {}, _defineProperty(_familyProxy2, FAMILY_CLASSIC, {
    'solid': 'fas',
    'regular': 'far',
    'light': 'fal',
    'thin': 'fat',
    'duotone': 'fad',
    'brands': 'fab',
    'kit': 'fak'
  }), _defineProperty(_familyProxy2, FAMILY_SHARP, {
    'solid': 'fass',
    'regular': 'fasr',
    'light': 'fasl'
  }), _familyProxy2));
  var PREFIX_TO_LONG_STYLE = familyProxy((_familyProxy3 = {}, _defineProperty(_familyProxy3, FAMILY_CLASSIC, {
    'fab': 'fa-brands',
    'fad': 'fa-duotone',
    'fak': 'fa-kit',
    'fal': 'fa-light',
    'far': 'fa-regular',
    'fas': 'fa-solid',
    'fat': 'fa-thin'
  }), _defineProperty(_familyProxy3, FAMILY_SHARP, {
    'fass': 'fa-solid',
    'fasr': 'fa-regular',
    'fasl': 'fa-light'
  }), _familyProxy3));
  var LONG_STYLE_TO_PREFIX = familyProxy((_familyProxy4 = {}, _defineProperty(_familyProxy4, FAMILY_CLASSIC, {
    'fa-brands': 'fab',
    'fa-duotone': 'fad',
    'fa-kit': 'fak',
    'fa-light': 'fal',
    'fa-regular': 'far',
    'fa-solid': 'fas',
    'fa-thin': 'fat'
  }), _defineProperty(_familyProxy4, FAMILY_SHARP, {
    'fa-solid': 'fass',
    'fa-regular': 'fasr',
    'fa-light': 'fasl'
  }), _familyProxy4));
  var FONT_WEIGHT_TO_PREFIX = familyProxy((_familyProxy5 = {}, _defineProperty(_familyProxy5, FAMILY_CLASSIC, {
    '900': 'fas',
    '400': 'far',
    'normal': 'far',
    '300': 'fal',
    '100': 'fat'
  }), _defineProperty(_familyProxy5, FAMILY_SHARP, {
    '900': 'fass',
    '400': 'fasr',
    '300': 'fasl'
  }), _familyProxy5));
  var oneToTen = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
  var oneToTwenty = oneToTen.concat([11, 12, 13, 14, 15, 16, 17, 18, 19, 20]);
  var DUOTONE_CLASSES = {
    GROUP: 'duotone-group',
    SWAP_OPACITY: 'swap-opacity',
    PRIMARY: 'primary',
    SECONDARY: 'secondary'
  };
  var prefixes = new Set();
  Object.keys(STYLE_TO_PREFIX[FAMILY_CLASSIC]).map(prefixes.add.bind(prefixes));
  Object.keys(STYLE_TO_PREFIX[FAMILY_SHARP]).map(prefixes.add.bind(prefixes));
  var RESERVED_CLASSES = [].concat(FAMILIES, _toConsumableArray(prefixes), ['2xs', 'xs', 'sm', 'lg', 'xl', '2xl', 'beat', 'border', 'fade', 'beat-fade', 'bounce', 'flip-both', 'flip-horizontal', 'flip-vertical', 'flip', 'fw', 'inverse', 'layers-counter', 'layers-text', 'layers', 'li', 'pull-left', 'pull-right', 'pulse', 'rotate-180', 'rotate-270', 'rotate-90', 'rotate-by', 'shake', 'spin-pulse', 'spin-reverse', 'spin', 'stack-1x', 'stack-2x', 'stack', 'ul', DUOTONE_CLASSES.GROUP, DUOTONE_CLASSES.SWAP_OPACITY, DUOTONE_CLASSES.PRIMARY, DUOTONE_CLASSES.SECONDARY]).concat(oneToTen.map(function (n) {
    return "".concat(n, "x");
  })).concat(oneToTwenty.map(function (n) {
    return "w-".concat(n);
  }));

  function bunker(fn) {
    try {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      fn.apply(void 0, args);
    } catch (e) {
      if (!PRODUCTION) {
        throw e;
      }
    }
  }

  var w = WINDOW || {};
  if (!w[NAMESPACE_IDENTIFIER]) w[NAMESPACE_IDENTIFIER] = {};
  if (!w[NAMESPACE_IDENTIFIER].styles) w[NAMESPACE_IDENTIFIER].styles = {};
  if (!w[NAMESPACE_IDENTIFIER].hooks) w[NAMESPACE_IDENTIFIER].hooks = {};
  if (!w[NAMESPACE_IDENTIFIER].shims) w[NAMESPACE_IDENTIFIER].shims = [];
  var namespace = w[NAMESPACE_IDENTIFIER];

  function normalizeIcons(icons) {
    return Object.keys(icons).reduce(function (acc, iconName) {
      var icon = icons[iconName];
      var expanded = !!icon.icon;

      if (expanded) {
        acc[icon.iconName] = icon.icon;
      } else {
        acc[iconName] = icon;
      }

      return acc;
    }, {});
  }

  function defineIcons(prefix, icons) {
    var params = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var _params$skipHooks = params.skipHooks,
        skipHooks = _params$skipHooks === void 0 ? false : _params$skipHooks;
    var normalized = normalizeIcons(icons);

    if (typeof namespace.hooks.addPack === 'function' && !skipHooks) {
      namespace.hooks.addPack(prefix, normalizeIcons(icons));
    } else {
      namespace.styles[prefix] = _objectSpread2(_objectSpread2({}, namespace.styles[prefix] || {}), normalized);
    }
    /**
     * Font Awesome 4 used the prefix of `fa` for all icons. With the introduction
     * of new styles we needed to differentiate between them. Prefix `fa` is now an alias
     * for `fas` so we'll ease the upgrade process for our users by automatically defining
     * this as well.
     */


    if (prefix === 'fas') {
      defineIcons('fa', icons);
    }
  }

  var icons = {
    
    "angle-down": [448,512,["8964"],"f107","M224 342.6l11.3-11.3 160-160L406.6 160 384 137.4l-11.3 11.3L224 297.4 75.3 148.7 64 137.4 41.4 160l11.3 11.3 160 160L224 342.6z"],
    "angle-right": [320,512,["8250"],"f105","M278.6 256l-11.3 11.3-160 160L96 438.6 73.4 416l11.3-11.3L233.4 256 84.7 107.3 73.4 96 96 73.4l11.3 11.3 160 160L278.6 256z"],
    "angle-up": [448,512,["8963"],"f106","M224 137.4l11.3 11.3 160 160L406.6 320 384 342.6l-11.3-11.3L224 182.6 75.3 331.3 64 342.6 41.4 320l11.3-11.3 160-160L224 137.4z"],
    "angles-right": [512,512,["187","angle-double-right"],"f101","M267.3 267.3L278.6 256l-11.3-11.3-160-160L96 73.4 73.4 96l11.3 11.3L233.4 256 84.7 404.7 73.4 416 96 438.6l11.3-11.3 160-160zm192 0L470.6 256l-11.3-11.3-160-160L288 73.4 265.4 96l11.3 11.3L425.4 256 276.7 404.7 265.4 416 288 438.6l11.3-11.3 160-160z"],
    "arrow-right": [448,512,["8594"],"f061","M435.3 267.3L446.6 256l-11.3-11.3-168-168L256 65.4 233.4 88l11.3 11.3L385.4 240 16 240 0 240l0 32 16 0 369.4 0L244.7 412.7 233.4 424 256 446.6l11.3-11.3 168-168z"],
    "arrow-right-arrow-left": [448,512,["8644","exchange"],"f0ec","M12.7 395.3L1.4 384l11.3-11.3 96-96L120 265.4 142.6 288l-11.3 11.3L62.6 368 432 368l16 0 0 32-16 0L62.6 400l68.7 68.7L142.6 480 120 502.6l-11.3-11.3-96-96zm422.6-256l-96 96L328 246.6 305.4 224l11.3-11.3L385.4 144 16 144 0 144l0-32 16 0 369.4 0L316.7 43.3 305.4 32 328 9.4l11.3 11.3 96 96L446.6 128l-11.3 11.3z"],
    "arrow-right-long": [512,512,["long-arrow-right"],"f178","M500.7 267.3L512 256l-11.3-11.3-144-144L345.4 89.4 322.7 112l11.3 11.3L450.7 240 16 240 0 240l0 32 16 0 434.7 0L334.1 388.7 322.7 400l22.6 22.6 11.3-11.3 144-144z"],
    "arrow-up": [384,512,["8593"],"f062","M203.3 44.7L192 33.4 180.7 44.7l-168 168L1.4 224 24 246.6l11.3-11.3L176 94.6V464v16h32V464 94.6L348.7 235.3 360 246.6 382.6 224l-11.3-11.3-168-168z"],
    "cart-shopping": [576,512,["128722","shopping-cart"],"f07a","M16 0H0V32H16 67.2l77.2 339.5 2.8 12.5H160 496h16V352H496 172.8l-14.5-64H496L566 64l10-32H542.5 100L95.6 12.5 92.8 0H80 16zm91.3 64H532.5l-60 192H151L107.3 64zM184 432a24 24 0 1 1 0 48 24 24 0 1 1 0-48zm0 80a56 56 0 1 0 0-112 56 56 0 1 0 0 112zm248-56a24 24 0 1 1 48 0 24 24 0 1 1 -48 0zm80 0a56 56 0 1 0 -112 0 56 56 0 1 0 112 0z"],
    "check": [448,512,["10004","10003"],"f00c","M448.1 118.2L437 129.7 173.6 404l-11.5 12-11.5-12L11.1 258.8 0 247.2l23.1-22.2 11.1 11.5L162.1 369.8 414 107.5 425 96l23.1 22.2z"],
    "chevron-down": [512,512,[],"f078","M256 406.6l11.3-11.3 192-192L470.6 192 448 169.4l-11.3 11.3L256 361.4 75.3 180.7 64 169.4 41.4 192l11.3 11.3 192 192L256 406.6z"],
    "chevron-right": [320,512,["9002"],"f054","M310.6 256l-11.3 11.3-192 192L96 470.6 73.4 448l11.3-11.3L265.4 256 84.7 75.3 73.4 64 96 41.4l11.3 11.3 192 192L310.6 256z"],
    "chevron-up": [512,512,[],"f077","M256 105.4l11.3 11.3 192 192L470.6 320 448 342.6l-11.3-11.3L256 150.6 75.3 331.3 64 342.6 41.4 320l11.3-11.3 192-192L256 105.4z"],
    "circle": [512,512,["61915","61708","11044","9899","9898","9679","128996","128995","128994","128993","128992","128309","128308"],"f111","M256 32a224 224 0 1 1 0 448 224 224 0 1 1 0-448zm0 480A256 256 0 1 0 256 0a256 256 0 1 0 0 512z"],
    "circle-chevron-left": [512,512,["chevron-circle-left"],"f137","M32 256a224 224 0 1 1 448 0A224 224 0 1 1 32 256zm480 0A256 256 0 1 0 0 256a256 256 0 1 0 512 0zM164.7 244.7L153.4 256l11.3 11.3 112 112L288 390.6 310.6 368l-11.3-11.3L198.6 256 299.3 155.3 310.6 144 288 121.4l-11.3 11.3-112 112z"],
    "circle-info": [512,512,["info-circle"],"f05a","M256 32a224 224 0 1 1 0 448 224 224 0 1 1 0-448zm0 480A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM192 352v32h16 96 16V352H304 272V240 224H256 216 200v32h16 24v96H208 192zm88-168V136H232v48h48z"],
    "download": [512,512,[],"f019","M272 16V0H240V16 329.4L139.3 228.7 128 217.4 105.4 240l11.3 11.3 128 128L256 390.6l11.3-11.3 128-128L406.6 240 384 217.4l-11.3 11.3L272 329.4V16zM140.1 320H32 0v32V480v32H32 480h32V480 352 320H480 371.9l-32 32H480V480H32V352H172.1l-32-32zM432 416a24 24 0 1 0 -48 0 24 24 0 1 0 48 0z"],
    "envelope": [512,512,["61443","9993","128386"],"f0e0","M32 159.2l224 154 224-154V96H32v63.2zM480 198L256 352 32 198V416H480V198zM0 416V176 96 64H32 480h32V96v80V416v32H480 32 0V416z"],
    "info": [192,512,[],"f129","M128 32V96H64V32h64zM16 160H32 96h16v16V448h64 16v32H176 16 0V448H16 80V192H32 16V160z"],
    "magnifying-glass": [512,512,["128269","search"],"f002","M384 208A176 176 0 1 0 32 208a176 176 0 1 0 352 0zM343.3 366C307 397.2 259.7 416 208 416C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208c0 51.7-18.8 99-50 135.3L510.5 487.9l-22.6 22.6L343.3 366z"],
    "pen": [512,512,["128394"],"f304","M0 512l6.8-34L32 352 361.4 22.6 384 0l22.6 22.6 82.7 82.7L512 128l-22.6 22.6L160 480 34 505.2 0 512zm144.2-61.5L398.1 196.7l-82.7-82.7L61.5 367.8 40.8 471.2l103.4-20.7zM420.7 174.1L466.7 128 384 45.3 337.9 91.3l82.7 82.7z"],
    "pencil": [512,512,["61504","9999","pencil-alt"],"f303","M0 512l6.8-34L32 352 361.4 22.6 384 0l22.6 22.6 82.7 82.7L512 128l-22.6 22.6L160 480 34 505.2 0 512zM176 400v18.7L398.1 196.7l-82.7-82.7L93.3 336H112h16v16 32h32 16v16zm-32 48V416H112 96V400 368H64c-.9 0-1.7-.1-2.5-.2L40.8 471.2l103.4-20.7c-.1-.8-.2-1.7-.2-2.5zM420.7 174.1L466.7 128 384 45.3 337.9 91.3l82.7 82.7zm-89.4 28.7l-128 128L192 342.1l-22.6-22.6 11.3-11.3 128-128L320 168.8l22.6 22.6-11.3 11.3z"],
    "phone": [512,512,["128379","128222"],"f095","M301.7 367.6l-21.3-12.3c-51.4-29.6-94.1-72.4-123.7-123.7l-12.3-21.3 17.3-17.3 40.7-40.7L156 36.2 32 58.7 32 64c0 229.7 186.3 416 416 416h5.3l22.5-124L359.7 309.5 319 350.2l-17.3 17.4zM352 272l160 64L480 512H448C200.6 512 0 311.4 0 64L0 32 176 0l64 160-55.6 55.6c26.8 46.5 65.5 85.2 112 112L352 272z"],
    "plus": [448,512,["61543","10133","add"],"2b","M240 64V48H208V64 240H32 16v32H32 208V448v16h32V448 272H416h16V240H416 240V64z"],
    "quote-left": [448,512,["8220","quote-left-alt"],"f10d","M0 208C0 146.1 50.1 96 112 96h32 16v32H144 112c-44.2 0-80 35.8-80 80v16H160h32v32V384v32H160 32 0V384 320 256 224 208zm32 48v64 64H160V256H32zm384 0H288v64 64H416V256zM256 320V256 224 208c0-61.9 50.1-112 112-112h32 16v32H400 368c-44.2 0-80 35.8-80 80v16H416h32v32V384v32H416 288 256V384 320z"],
    "quote-right": [448,512,["8221","quote-right-alt"],"f10e","M448 304c0 61.9-50.1 112-112 112H304 288V384h16 32c44.2 0 80-35.8 80-80V288H288 256V256 128 96h32H416h32v32 64 64 32 16zm-32-48V192 128H288V256H416zM32 256H160V192 128H32V256zm160-64v64 32 16c0 61.9-50.1 112-112 112H48 32V384H48 80c44.2 0 80-35.8 80-80V288H32 0V256 128 96H32 160h32v32 64z"],
    "trash": [448,512,[],"f1f8","M151.1 0H160 288h8.9l4.7 7.5L336.9 64h47.1H416h32V96H413.7L384 512H64L34.3 96H0V64H32 64.1h47.1L146.4 7.5 151.1 0zm-2.3 64H299.1l-20-32H168.9l-20 32zM66.4 96L93.8 480H354.2L381.6 96H66.4z"],
    "trash-can": [448,512,["61460","trash-alt"],"f2ed","M160 0h-8.9l-4.7 7.5L111.1 64H64 32 0V96H32V480v32H64 384h32V480 96h32V64H416 384 336.9L301.6 7.5 296.9 0H288 160zM299.1 64H148.9l20-32H279.1l20 32zM64 480V96H384V480H64zm80-304V160H112v16V400v16h32V400 176zm96 0V160H208v16V400v16h32V400 176zm96 0V160H304v16V400v16h32V400 176z"],
    "xmark": [384,512,["215","10060","10006","10005","128473","close","multiply","remove","times"],"f00d","M192 233.4L59.5 100.9 36.9 123.5 169.4 256 36.9 388.5l22.6 22.6L192 278.6 324.5 411.1l22.6-22.6L214.6 256 347.1 123.5l-22.6-22.6L192 233.4z"]

  };
  var prefixes$1 = [null    ,'fasl',
    ,'fa-light'

  ];
  bunker(function () {
    var _iterator = _createForOfIteratorHelper(prefixes$1),
        _step;

    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var prefix = _step.value;
        if (!prefix) continue;
        defineIcons(prefix, icons);
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  });

}());
