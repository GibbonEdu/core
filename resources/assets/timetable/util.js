function Util() { }
Util.hasClass = function (el, className) {
    return el.classList.contains(className);
};
Util.addClass = function (el, className) {
    var classList = className.split(" ");
    el.classList.add(classList[0]);
    if (classList.length > 1) Util.addClass(el, classList.slice(1).join(" "));
};
Util.removeClass = function (el, className) {
    var classList = className.split(" ");
    el.classList.remove(classList[0]);
    if (classList.length > 1) Util.removeClass(el, classList.slice(1).join(" "));
};
Util.toggleClass = function (el, className, bool) {
    if (bool) Util.addClass(el, className);
    else Util.removeClass(el, className);
};
Util.setAttributes = function (el, attrs) {
    for (var key in attrs) {
        el.setAttribute(key, attrs[key]);
    }
};
Util.getChildrenByClassName = function (el, className) {
    var children = el.children,
        childrenByClass = [];
    for (var i = 0; i < children.length; i++) {
        if (Util.hasClass(children[i], className)) childrenByClass.push(children[i]);
    }
    return childrenByClass;
};
Util.is = function (elem, selector) {
    if (selector.nodeType) {
        return elem === selector;
    }
    var qa = typeof selector === "string" ? document.querySelectorAll(selector) : selector,
        length = qa.length;
    while (length--) {
        if (qa[length] === elem) {
            return true;
        }
    }
    return false;
};
Util.setHeight = function (start, to, element, duration, cb, timeFunction) {
    var change = to - start,
        currentTime = null;
    var animateHeight = function (timestamp) {
        if (!currentTime) currentTime = timestamp;
        var progress = timestamp - currentTime;
        if (progress > duration) progress = duration;
        var val = parseInt((progress / duration) * change + start);
        if (timeFunction) {
            val = Math[timeFunction](progress, start, to - start, duration);
        }
        element.style.height = val + "px";
        if (progress < duration) {
            window.requestAnimationFrame(animateHeight);
        } else {
            if (cb) cb();
        }
    };
    element.style.height = start + "px";
    window.requestAnimationFrame(animateHeight);
};
Util.scrollTo = function (final, duration, cb, scrollEl) {
    var element = scrollEl || window;
    var start = element.scrollTop || document.documentElement.scrollTop,
        currentTime = null;
    if (!scrollEl) start = window.scrollY || document.documentElement.scrollTop;
    var animateScroll = function (timestamp) {
        if (!currentTime) currentTime = timestamp;
        var progress = timestamp - currentTime;
        if (progress > duration) progress = duration;
        var val = Math.easeInOutQuad(progress, start, final - start, duration);
        element.scrollTo(0, val);
        if (progress < duration) {
            window.requestAnimationFrame(animateScroll);
        } else {
            cb && cb();
        }
    };
    window.requestAnimationFrame(animateScroll);
};
Util.moveFocus = function (element) {
    if (!element) element = document.getElementsByTagName("body")[0];
    element.focus();
    if (document.activeElement !== element) {
        element.setAttribute("tabindex", "-1");
        element.focus();
    }
};
Util.getIndexInArray = function (array, el) {
    return Array.prototype.indexOf.call(array, el);
};
Util.cssSupports = function (property, value) {
    return CSS.supports(property, value);
};
Util.extend = function () {
    var extended = {};
    var deep = false;
    var i = 0;
    var length = arguments.length;
    if (Object.prototype.toString.call(arguments[0]) === "[object Boolean]") {
        deep = arguments[0];
        i++;
    }
    var merge = function (obj) {
        for (var prop in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, prop)) {
                if (deep && Object.prototype.toString.call(obj[prop]) === "[object Object]") {
                    extended[prop] = extend(true, extended[prop], obj[prop]);
                } else {
                    extended[prop] = obj[prop];
                }
            }
        }
    };
    for (; i < length; i++) {
        var obj = arguments[i];
        merge(obj);
    }
    return extended;
};
Util.osHasReducedMotion = function () {
    if (!window.matchMedia) return false;
    var matchMediaObj = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (matchMediaObj) return matchMediaObj.matches;
    return false;
};
Math.easeInOutQuad = function (t, b, c, d) {
    t /= d / 2;
    if (t < 1) return (c / 2) * t * t + b;
    t--;
    return (-c / 2) * (t * (t - 2) - 1) + b;
};
Math.easeInQuart = function (t, b, c, d) {
    t /= d;
    return c * t * t * t * t + b;
};
Math.easeOutQuart = function (t, b, c, d) {
    t /= d;
    t--;
    return -c * (t * t * t * t - 1) + b;
};
Math.easeInOutQuart = function (t, b, c, d) {
    t /= d / 2;
    if (t < 1) return (c / 2) * t * t * t * t + b;
    t -= 2;
    return (-c / 2) * (t * t * t * t - 2) + b;
};
Math.easeOutElastic = function (t, b, c, d) {
    var s = 1.70158;
    var p = d * 0.7;
    var a = c;
    if (t == 0) return b;
    if ((t /= d) == 1) return b + c;
    if (!p) p = d * 0.3;
    if (a < Math.abs(c)) {
        a = c;
        var s = p / 4;
    } else var s = (p / (2 * Math.PI)) * Math.asin(c / a);
    return a * Math.pow(2, -10 * t) * Math.sin(((t * d - s) * (2 * Math.PI)) / p) + c + b;
};
(function () {
    var focusTab = document.getElementsByClassName("js-tab-focus"),
        shouldInit = false,
        outlineStyle = false,
        eventDetected = false;
    function detectClick() {
        if (focusTab.length > 0) {
            resetFocusStyle(false);
            window.addEventListener("keydown", detectTab);
        }
        window.removeEventListener("mousedown", detectClick);
        outlineStyle = false;
        eventDetected = true;
    }
    function detectTab(event) {
        if (event.keyCode !== 9) return;
        resetFocusStyle(true);
        window.removeEventListener("keydown", detectTab);
        window.addEventListener("mousedown", detectClick);
        outlineStyle = true;
    }
    function resetFocusStyle(bool) {
        var outlineStyle = bool ? "" : "none";
        for (var i = 0; i < focusTab.length; i++) {
            focusTab[i].style.setProperty("outline", outlineStyle);
        }
    }
    function initFocusTabs() {
        if (shouldInit) {
            if (eventDetected) resetFocusStyle(outlineStyle);
            return;
        }
        shouldInit = focusTab.length > 0;
        window.addEventListener("mousedown", detectClick);
    }
    initFocusTabs();
    window.addEventListener("initFocusTabs", initFocusTabs);
})();
function resetFocusTabsStyle() {
    window.dispatchEvent(new CustomEvent("initFocusTabs"));
}
