/* do not edit this file! */
/**
 * https://github.com/tbela99/critical/blob/master/dist/browser.js
 */
var critical = function(e) {
    "use strict";
    function t(e, t) {
        let r = e.matchesSelector || e.webkitMatchesSelector || e.mozMatchesSelector || e.msMatchesSelector;
        if (r) try {
            return r.call(e, t);
        } catch (e) {
            return !1;
        } else {
            let r = e.ownerDocument.querySelectorAll(t), n = r.length;
            for (;n && n--; ) if (r[n] === e) return !0;
        }
        return !1;
    }
    /**
     * resolve to absolute for external urls, relative for same domain
     * @param {string} path
     * @param {string} from
     * @returns {string}
     */    function r(e, t) {
        if ("data:" === e.substr(0, 5)) return e;
        const r = new URL(t, window.location), n = new URL(e, r);
        return r.protocol != n.protocol || r.host != n.host || n.host != window.location.host || r.port != n.port || n.port != window.location.port || r.port != n.port || n.protocol != window.location.protocol ? n.toString() : n.pathname;
    }
    /**
     * {Object} options
     * - fonts: generate javascript font loading script
     * @returns {Promise<{styles: string[], fonts: object[]}>}
     */    async function n(e = {}) {
        e = Object.assign({
            // not implemented... yet
            // inlineFonts: false,
            fonts: !0
        }, e);
        const n = new Set, l = [ "all", "print", "" ], o = [], a = window.innerHeight, s = document.createTreeWalker(document, NodeFilter.SHOW_ELEMENT, (function(e) {
            return NodeFilter.FILTER_ACCEPT;
        }), !0), i = new Set, c = new Set, f = new Map;
        if ([].push.apply(o, Array.from(document.styleSheets).filter(e => "" == e.media.mediaText || "print" != e.media.mediaText && window.matchMedia(e.media.mediaText).matches).map(e => {
            try {
                return Array.from(e.cssRules || e.rules).map(e => ({
                    rule: e,
                    match: !1
                }));
            } catch (t) {
                console.error(JSON.stringify({
                    message: t.message,
                    stylesheet: e.href
                }, null, 1));
            }
            return !1;
        }).filter(e => e).flat()), 0 === o.length) return [];
        let u, p, h = o.length;
        for (;s.nextNode(); ) {
            let e = s.currentNode;
            if (e.nodeType == Node.ELEMENT_NODE && (e.getBoundingClientRect().top < a && ![ "SCRIPT", "LINK", "HEAD", "META", "TITLE" ].includes(e.tagName))) for (let r = 0; r < h; r++) if (!o[r].match) if (o[r].rule instanceof CSSStyleRule) t(e, o[r].rule.selectorText) && (o[r].match = !0, 
            o[r].rule.style.getPropertyValue("font-family") && o[r].rule.style.getPropertyValue("font-family").split(/\s*,\s*/).forEach(e => "inherit" != e && c.add(e.replace(/(['"])([^\1\s]+)\1/, "$2")))); else if (o[r].rule instanceof CSSMediaRule) {
                let e = Array.from(o[r].rule.cssRules || o[r].rule.rules).map(e => ({
                    rule: e,
                    match: !1
                }));
                e.unshift(r + 1, 0), o.splice.apply(o, e), h = o.length;
            } else if (o[r].rule instanceof CSSImportRule) try {
                if (!window.matchMedia(o[r].rule.media.mediaText).matches) continue;
                let e = Array.from(o[r].rule.styleSheet.cssRules || o[r].rule.styleSheet.rules).map(e => ({
                    rule: e,
                    match: !1
                }));
                e.unshift(r + 1, 0), o.splice.apply(o, e), h = o.length, n.add("/* @import: " + o[r].rule.href + " from " + (o[r].rule.parentStyleSheet.href || "inline #" + d) + " */");
            } catch (e) {
                // console.error(1);
                console.error(e.message), console.error(o[r].rule.href);
            } else o[r].rule instanceof CSSFontFaceRule && o[r].rule.style.getPropertyValue("font-family") && o[r].rule.style.getPropertyValue("src") && i.add(o[r].rule);
        }
        let y = "", d = -1;
        e: for (let e = 0; e < h; e++) {
            if (!o[e].match) continue;
            u = o[e].rule;
            let t = !1;
            if (f.has(u.parentStyleSheet) ? y && y != f.get(u.parentStyleSheet).file && (t = !0) : (
            f.set(u.parentStyleSheet, {
                base: (u.parentStyleSheet.href && u.parentStyleSheet.href.replace(/[?#].*/, "") || location.pathname).replace(/([^/]+)$/, ""),
                file: u.parentStyleSheet.href || "inline #" + ++d
            }), t = !0), t) try {
                console.log("analysing " + f.get(u.parentStyleSheet).file), n.add("/* file: " + f.get(u.parentStyleSheet).file + " */");
            } catch (e) {
                console.error(e.message), console.error(u.parentStyleSheet);
            }
            for (y = f.get(u.parentStyleSheet).file, p = u.cssText, "inline" != y && (
            // resolve url()
            p = p.replace(/url\(([^)%\s]*?)\)/g, (function(e, t) {
                return "url(" + r(t = (t = t.trim()).replace(/^(['"])([^\1\s]+)\1/, "$2"), f.get(u.parentStyleSheet).base) + ")";
            }))); u.parentRule; ) {
                if (
                /**
                         *
                         * @type {CSSMediaRule}
                         */
                u = u.parentRule, "print" == u.conditionText) continue e;
                if (l.includes(u.conditionText) || (p = "@media " + u.conditionText + " {" + p + "}"), 
                !u.parentRule) break;
            }
            if (u.parentStyleSheet) {
                let e = u.parentStyleSheet.media.mediaText;
                if ("print" == e) continue e;
                l.includes(e) || (p = "@media " + e + " {" + p + "}");
            }
            n.has(p) && n.delete(p), n.add(p);
        }
        const m = new Map;
        if (e.fonts) {
            let e, t, n, l, o;
            for (l of i) if (l.style.getPropertyValue("font-family").split(/\s*,\s*/).some(e => c.has(e.replace(/(['"])([^\1\s]+)\1/, "$2")))) {
                for (o = {
                    fontFamily: l.style.getPropertyValue("font-family").replace(/(['"])([^\1\s]+)\1/, "$2"),
                    src: l.style.getPropertyValue("src").replace(/(^|[,\s*])local\([^)]+\)\s*,?\s*?/g, "").replace(/url\(([^)%\s]+)\)([^,]*)(,?)\s*/g, (e, t, n, o) => (t = t.replace(/(['"])([^\1\s]+)\1/, "$2"), 
                    f.has(l.parentStyleSheet) || f.set(l.parentStyleSheet, {
                        base: l.parentStyleSheet.href.replace(/([^/]+)$/, ""),
                        file: l.parentStyleSheet.href
                    }), "url(" + r(t, f.get(l.parentStyleSheet).base) + ")" + o)).trim(),
                    properties: {}
                }, e = l.style.length; e--; ) t = l.style.item(e), n = l.style.getPropertyValue(t), 
                "font-family" != t && "src" != t && "" !== n && void 0 !== n && (o.properties[t.replace(/([A-Z])/g, (e, t) => "-" + t.toLowerCase())] = n);
                m.set(JSON.stringify(o), o);
            }
        }
        return {
            styles: [ ...n ],
            fonts: [ ...m.values() ]
        };
    }
    /**
     *
     * @param {string[]} content
     * @param {string} filename
     * @param {string} mimetype
     * @return {Promise<string[]>}
     */    async function l(e, t, r = "application/octet-stream; charset=utf-8") {
        const n = URL.createObjectURL(new Blob(e, {
            type: r
        })), l = document.createElement("a");
        
                return document.body.append(l), l.style.display = "none", l.download = t, 
        l.href = n, 
        l.dispatchEvent(new MouseEvent("click")), URL.revokeObjectURL(n), e;
    }
    /**
     *
     * @param {string[]} fonts
     */    function o(e) {
        return "/* font preloader script: " + e.length + ' */\n"fonts" in document && ' + JSON.stringify([ ...e ], null, 1) + ".forEach(font => new FontFace(font.fontFamily, font.src, font.properties).load().then(font => document.fonts.add(font)))";
    }
    /**
     *
     * @param {string} filename
     * @return {Promise<{styles: string[], fonts: object[]}>}
     */    return e.download = async function(e = "critical.css", t = {}) {
        return n(t).then(async t => (await l(t.styles, e, "text/css; charset=utf-8").then(async () => {
            t.fonts.length > 0 && await l([ o(t.fonts) ], e.replace(/\.css$/, ".js"), "text/javascript; charset=utf-8");
        }), t));
    }, e.extract = n, e.fontscript = o, Object.defineProperty(e, "__esModule", {
        value: !0
    }), e;
}({});