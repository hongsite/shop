/**
 * hst-laydate.js
 * ============================================================
 * 第一部分：hst.js - 轻量级 DOM 操作库 (Chrome 79+ 兼容版)
 * 第二部分：laydate.js - 独立日期时间选择器 (Chrome 79+ 兼容版)
 * ============================================================
 * 修复：
 *   1. laydate 使用 WeakMap，Chrome 79+ 原生支持
 *   2. hst.js 全局 click 委托排除 laydate 元素，避免拦截
 *   3. laydate._laydateBound 逻辑修复，手动 render 可更新配置
 *   4. documentClickHandler 用 contains 判断，避免误关面板
 * ============================================================
 */
(function(window, undefined) {
    'use strict';

    /* ==========================================================
     * 第一部分：hst.js
     * ========================================================== */

    // ---------- 辅助函数 ----------
    function getMatchesMethod(el) {
        return el.matches || el.webkitMatchesSelector || el.msMatchesSelector ||
               el.mozMatchesSelector || el.oMatchesSelector;
    }

    function addEvent(el, type, handler, useCapture) {
        el.addEventListener(type, handler, !!useCapture);
    }

    function removeEvent(el, type, handler, useCapture) {
        el.removeEventListener(type, handler, !!useCapture);
    }

    // 事件对象归一化（Chrome 79+ 基本不需要，但保留以防万一）
    function normalizeEvent(e) {
        e = e || window.event;
        if (e.which === undefined) e.which = e.keyCode || e.charCode || 0;
        if (e.keyCode === undefined) e.keyCode = e.which;
        if (!e.preventDefault) e.preventDefault = function() { e.returnValue = false; };
        if (!e.stopPropagation) e.stopPropagation = function() { e.cancelBubble = true; };
        if (!e.stopImmediatePropagation) {
            e.stopImmediatePropagation = function() {
                e.cancelBubble = true;
                e.returnValue = false;
            };
        }
        return e;
    }

    function normalizeSelector(selector) {
        if (typeof selector !== 'string') return selector;
        var regex = /\[([^\]]*?)=([^\]'"]+?)(?=\]|$)\]/g;
        return selector.replace(regex, function(match, attr, val) {
            if (val.indexOf('"') === -1 && val.indexOf("'") === -1) {
                return '[' + attr + '="' + val + '"]';
            }
            return match;
        });
    }

    function parseSelector(selector) {
        if (typeof selector !== 'string') return { base: '', filters: [] };
        selector = normalizeSelector(selector);
        var base = selector;
        var filters = [];

        var eqRegex = /:eq\((\d+)\)/g;
        var eqMatch;
        while ((eqMatch = eqRegex.exec(selector)) !== null) {
            filters.push({ type: 'eq', index: parseInt(eqMatch[1], 10) });
            base = base.replace(eqMatch[0], '');
        }

        var firstRegex = /:first(?!-)/g;
        if (firstRegex.test(selector)) {
            filters.push({ type: 'first' });
            base = base.replace(firstRegex, '');
        }

        var lastRegex = /:last(?!-)/g;
        if (lastRegex.test(selector)) {
            filters.push({ type: 'last' });
            base = base.replace(lastRegex, '');
        }

        var nthRegex = /:nth-of-type\(([^)]+)\)/g;
        var nthMatch;
        while ((nthMatch = nthRegex.exec(selector)) !== null) {
            filters.push({ type: 'nth-of-type', formula: nthMatch[1] });
            base = base.replace(nthMatch[0], '');
        }

        var pseudoRegex = /:(before|after)\b/g;
        if (pseudoRegex.test(selector)) {
            filters.push({ type: 'pseudo' });
            base = base.replace(pseudoRegex, '');
        }
        base = base.trim();
        return { base: base, filters: filters };
    }

    function applyFilters(elements, filters) {
        if (!elements.length || !filters.length) return elements;
        var result = elements.slice();
        for (var i = 0; i < filters.length; i++) {
            var filter = filters[i];
            if (filter.type === 'eq') {
                var idx = filter.index;
                result = (idx >= 0 && idx < result.length) ? [result[idx]] : [];
            } else if (filter.type === 'first') {
                result = result.length ? [result[0]] : [];
            } else if (filter.type === 'last') {
                result = result.length ? [result[result.length - 1]] : [];
            } else if (filter.type === 'nth-of-type') {
                result = filterNthOfType(result, filter.formula);
            } else if (filter.type === 'pseudo') {
                return [];
            }
        }
        return result;
    }

    function filterNthOfType(elements, formula) {
        var groups = {};
        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            var parent = el.parentNode;
            var tag = el.tagName;
            var key = (parent ? parent.id || parent.tagName : 'null') + '|' + tag;
            if (!groups[key]) groups[key] = [];
            groups[key].push(el);
        }
        var filtered = [];
        for (var g in groups) {
            if (!groups.hasOwnProperty(g)) continue;
            var list = groups[g];
            for (var j = 0; j < list.length; j++) {
                if (matchNth(j + 1, formula)) filtered.push(list[j]);
            }
        }
        return filtered;
    }

    function matchNth(index, formula) {
        formula = formula.trim();
        if (formula === 'odd') return index % 2 === 1;
        if (formula === 'even') return index % 2 === 0;
        var parts = formula.split(/[nN]/);
        if (parts.length === 2) {
            var a = parts[0] === '' ? 1 : (parts[0] === '-' ? -1 : parseInt(parts[0], 10));
            var b = parts[1] === '' ? 0 : parseInt(parts[1], 10);
            return (index - b) % a === 0 && (index - b) / a >= 0;
        } else if (parts.length === 1) {
            return index === parseInt(formula, 10);
        }
        return false;
    }

    function uniqueArray(arr) {
        var unique = [];
        for (var i = 0; i < arr.length; i++) {
            if (unique.indexOf(arr[i]) === -1) unique.push(arr[i]);
        }
        return unique;
    }

    function parseContentToNodes(content) {
        var nodes = [];
        if (typeof content === 'string') {
            var div = document.createElement('div');
            div.innerHTML = content;
            for (var i = 0; i < div.childNodes.length; i++) {
                nodes.push(div.childNodes[i].cloneNode(true));
            }
        } else if (content.nodeType) {
            nodes.push(content.cloneNode(true));
        } else if (content instanceof Kodo) {
            content.each(function() { nodes.push(this.cloneNode(true)); });
        }
        return nodes;
    }

    function getComputedStyleSafe(el) {
        return window.getComputedStyle(el, null);
    }

    function getContentWidth(el) {
        if (el === window) return window.innerWidth;
        if (el === document) return document.documentElement.clientWidth;
        var rect = el.getBoundingClientRect();
        var cs = getComputedStyleSafe(el);
        var paddingLeft = parseFloat(cs.paddingLeft) || 0;
        var paddingRight = parseFloat(cs.paddingRight) || 0;
        var borderLeft = parseFloat(cs.borderLeftWidth) || 0;
        var borderRight = parseFloat(cs.borderRightWidth) || 0;
        return rect.width - paddingLeft - paddingRight - borderLeft - borderRight;
    }

    function getContentHeight(el) {
        if (el === window) return window.innerHeight;
        if (el === document) return document.documentElement.clientHeight;
        var rect = el.getBoundingClientRect();
        var cs = getComputedStyleSafe(el);
        var paddingTop = parseFloat(cs.paddingTop) || 0;
        var paddingBottom = parseFloat(cs.paddingBottom) || 0;
        var borderTop = parseFloat(cs.borderTopWidth) || 0;
        var borderBottom = parseFloat(cs.borderBottomWidth) || 0;
        return rect.height - paddingTop - paddingBottom - borderTop - borderBottom;
    }

    function isPlainObject(obj) {
        return obj && typeof obj === 'object' && !Array.isArray(obj) && obj !== null &&
               !obj.nodeType && !(obj instanceof Kodo);
    }

    function contains(parent, child) {
        if (!parent || !child) return false;
        return parent.contains(child);
    }

    // ---------- 全局委托管理器 ----------
    var liveDelegates = [];
    var globalListenerRegistered = {};

    function getSpecificity(selector) {
        var len = selector.length;
        var descendantCount = (selector.match(/\s+/g) || []).length;
        var idCount = (selector.match(/#/g) || []).length;
        var classCount = (selector.match(/\./g) || []).length;
        return len + descendantCount * 10 + idCount * 100 + classCount * 10;
    }

    // 判断元素是否为 laydate 相关元素（输入框或面板内部）
    function isLaydateElement(el) {
        while (el && el !== document) {
            if (el.className && typeof el.className === 'string') {
                if (el.className.indexOf('laydate') !== -1) return true;
            }
            el = el.parentNode;
        }
        return false;
    }

    function handleGlobalEvent(e, overrideType) {
        e = normalizeEvent(e);
        var eventType = overrideType || e.type;
        var target = e.target;

        // 关键修复：laydate 相关元素不参与全局委托，交给 laydate 自己处理
        if (eventType === 'click' || eventType === 'mousedown' || eventType === 'mouseup') {
            if (isLaydateElement(target)) return;
        }

        var matched = [];

        for (var i = 0; i < liveDelegates.length; i++) {
            var d = liveDelegates[i];
            if (d.eventType !== eventType) continue;
            var el = target;
            while (el && el !== document) {
                var matchesFn = getMatchesMethod(el);
                if (matchesFn && matchesFn.call(el, d.selector)) {
                    matched.push({ delegate: d, element: el });
                    break;
                }
                el = el.parentNode;
            }
        }

        matched.sort(function(a, b) {
            return b.delegate.specificity - a.delegate.specificity;
        });

        for (var j = 0; j < matched.length; j++) {
            var item = matched[j];
            var result = item.delegate.callback.call(item.element, e);
            if (result === false) {
                e.stopImmediatePropagation();
                e.preventDefault();
                break;
            }
        }
    }

    function handleGlobalMouseEnter(e) {
        e = normalizeEvent(e);
        var target = e.target;
        var related = e.relatedTarget;
        if (related && target && contains(target, related)) return;
        handleGlobalEvent(e, 'mouseenter');
    }

    function handleGlobalMouseLeave(e) {
        e = normalizeEvent(e);
        var target = e.target;
        var related = e.relatedTarget;
        if (related && target && contains(target, related)) return;
        handleGlobalEvent(e, 'mouseleave');
    }

    // ---------- Kodo 核心 ----------
    function Kodo(selector, context) {
        if (!(this instanceof Kodo)) return new Kodo(selector, context);
        this.elements = [];

        if (typeof selector === 'function') {
            Kodo.ready(selector);
            return this;
        }
        if (!selector) return this;

        if (selector === window || selector === document) {
            this.elements = [selector];
            return this;
        }
        if (selector.nodeType) {
            this.elements = [selector];
            return this;
        }
        if (selector instanceof Kodo) {
            this.elements = selector.elements.slice();
            return this;
        }
        if (typeof selector === 'string') {
            var trimmed = selector.trim();
            if (trimmed.indexOf('<') === 0 && trimmed.indexOf('>') > 0) {
                var div = document.createElement('div');
                div.innerHTML = trimmed;
                this.elements = Array.prototype.slice.call(div.childNodes);
                return this;
            }
            var parsed = parseSelector(selector);
            var nodes = [];
            if (parsed.base) {
                if (context) {
                    var ctx = (context instanceof Kodo) ? context.elements[0] : context;
                    nodes = Array.prototype.slice.call((ctx && ctx.nodeType ? ctx : document).querySelectorAll(parsed.base));
                } else {
                    nodes = Array.prototype.slice.call(document.querySelectorAll(parsed.base));
                }
            }
            this.elements = applyFilters(nodes, parsed.filters);
            this._selector = selector;
            return this;
        }
        if (selector.length !== undefined) {
            this.elements = Array.prototype.slice.call(selector);
            return this;
        }
        if (isPlainObject(selector)) {
            var entries = [];
            for (var key in selector) {
                if (selector.hasOwnProperty(key)) {
                    entries.push({ key: key, value: selector[key] });
                }
            }
            this.elements = entries;
            this._isObjectWrapper = true;
            return this;
        }
    }

    Kodo.prototype = {
        constructor: Kodo,
        get length() { return this.elements.length; },

        get: function(index) {
            if (index === undefined) return this.elements.slice();
            return this.elements[index];
        },

        each: function(callback) {
            if (this._isObjectWrapper) {
                for (var i = 0; i < this.elements.length; i++) {
                    var item = this.elements[i];
                    if (callback.call(item.value, item.key, item.value) === false) break;
                }
            } else {
                for (var i = 0; i < this.elements.length; i++) {
                    if (callback.call(this.elements[i], i, this.elements[i]) === false) break;
                }
            }
            return this;
        },

        find: function(selector) {
            var parsed = parseSelector(selector);
            var result = [];
            this.each(function() {
                result = result.concat(Array.prototype.slice.call(this.querySelectorAll(parsed.base)));
            });
            result = uniqueArray(result);
            result = applyFilters(result, parsed.filters);
            var newKodo = Kodo(result);
            newKodo._selector = selector;
            return newKodo;
        },

        parent: function() {
            var parents = [];
            this.each(function() {
                var p = this.parentNode;
                if (p && parents.indexOf(p) === -1) parents.push(p);
            });
            return Kodo(parents);
        },

        html: function(content) {
            if (content === undefined) return this.elements[0] ? this.elements[0].innerHTML : undefined;
            this.each(function() { this.innerHTML = content; });
            return this;
        },

        text: function(content) {
            if (content === undefined) {
                var texts = [];
                this.each(function() { texts.push(this.textContent || ''); });
                return texts.join('');
            }
            this.each(function() { this.textContent = content; });
            return this;
        },

        val: function(value) {
            if (value === undefined) return this.elements[0] ? this.elements[0].value : undefined;
            this.each(function() { this.value = value; });
            return this;
        },

        attr: function(name, value) {
            if (value === undefined) return this.elements[0] ? this.elements[0].getAttribute(name) : undefined;
            this.each(function() { this.setAttribute(name, value); });
            return this;
        },

        data: function(key, value) {
            if (value === undefined) {
                if (!this.elements[0]) return undefined;
                var el = this.elements[0];
                var dataVal = el.dataset ? el.dataset[key] : undefined;
                if (dataVal !== undefined) return dataVal;
                if (el._kodoData && el._kodoData[key] !== undefined) {
                    return el._kodoData[key];
                }
                return undefined;
            }
            this.each(function() {
                if (!this._kodoData) this._kodoData = {};
                this._kodoData[key] = value;
            });
            return this;
        },

        css: function(name, value) {
            if (value === undefined && typeof name === 'string') {
                if (!this.elements[0]) return undefined;
                var cs = getComputedStyleSafe(this.elements[0]);
                return cs[name];
            }
            if (typeof name === 'object') {
                for (var prop in name) {
                    if (name.hasOwnProperty(prop)) this.each(function() { this.style[prop] = name[prop]; });
                }
                return this;
            }
            this.each(function() { this.style[name] = value; });
            return this;
        },

        width: function(val) {
            if (val === undefined) {
                if (this.elements.length === 0) return undefined;
                return getContentWidth(this.elements[0]);
            } else {
                var value = typeof val === 'number' ? val + 'px' : val;
                this.each(function() {
                    if (this && this !== window && this !== document && this.style) {
                        this.style.width = value;
                    }
                });
                return this;
            }
        },

        height: function(val) {
            if (val === undefined) {
                if (this.elements.length === 0) return undefined;
                return getContentHeight(this.elements[0]);
            } else {
                var value = typeof val === 'number' ? val + 'px' : val;
                this.each(function() {
                    if (this && this !== window && this !== document && this.style) {
                        this.style.height = value;
                    }
                });
                return this;
            }
        },

        offset: function() {
            if (this.elements.length === 0) return null;
            var rect = this.elements[0].getBoundingClientRect();
            var docEl = document.documentElement;
            var scrollLeft = window.pageXOffset || docEl.scrollLeft || 0;
            var scrollTop = window.pageYOffset || docEl.scrollTop || 0;
            return {
                left: rect.left + scrollLeft,
                top: rect.top + scrollTop
            };
        },

        outerWidth: function(includeMargin) {
            if (this.elements.length === 0) return 0;
            var el = this.elements[0];
            if (el === window) return window.innerWidth;
            if (el === document) return document.documentElement.clientWidth;
            var cs = getComputedStyleSafe(el);
            var width = parseFloat(cs.width) + (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0) +
                       (parseFloat(cs.borderLeftWidth) || 0) + (parseFloat(cs.borderRightWidth) || 0);
            if (includeMargin) {
                width += (parseFloat(cs.marginLeft) || 0) + (parseFloat(cs.marginRight) || 0);
            }
            return width;
        },

        innerWidth: function() {
            if (this.elements.length === 0) return 0;
            var el = this.elements[0];
            if (el === window) return window.innerWidth;
            if (el === document) return document.documentElement.clientWidth;
            var cs = getComputedStyleSafe(el);
            return parseFloat(cs.width) + (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
        },

        innerHeight: function() {
            if (this.elements.length === 0) return 0;
            var el = this.elements[0];
            if (el === window) return window.innerHeight;
            if (el === document) return document.documentElement.clientHeight;
            var cs = getComputedStyleSafe(el);
            return parseFloat(cs.height) + (parseFloat(cs.paddingTop) || 0) + (parseFloat(cs.paddingBottom) || 0);
        },

        index: function(selector) {
            if (this.elements.length === 0) return -1;
            var el = this.elements[0];
            if (selector === undefined) {
                var parent = el.parentNode;
                if (!parent) return -1;
                var children = parent.children;
                for (var i = 0; i < children.length; i++) {
                    if (children[i] === el) return i;
                }
                return -1;
            } else {
                var matched = $(selector).elements;
                for (var i = 0; i < matched.length; i++) {
                    if (matched[i] === el) return i;
                }
                return -1;
            }
        },

        show: function() {
            this.each(function() {
                this.style.display = '';
                if (getComputedStyleSafe(this).display === 'none') this.style.display = 'block';
            });
            return this;
        },

        hide: function() {
            this.each(function() { this.style.display = 'none'; });
            return this;
        },

        addClass: function(className) {
            this.each(function() { this.classList.add(className); });
            return this;
        },

        removeClass: function(className) {
            this.each(function() { this.classList.remove(className); });
            return this;
        },

        toggleClass: function(className) {
            this.each(function() { this.classList.toggle(className); });
            return this;
        },

        hasClass: function(className) {
            if (this.length === 0) return false;
            return this.elements[0].classList.contains(className);
        },

        remove: function() {
            this.each(function() { if (this.parentNode) this.parentNode.removeChild(this); });
            return this;
        },

        empty: function() {
            this.each(function() { this.innerHTML = ''; });
            return this;
        },

        append: function(content) {
            this.each(function() {
                var self = this;
                if (typeof content === 'string') self.insertAdjacentHTML('beforeend', content);
                else if (content && content.nodeType) self.appendChild(content.cloneNode(true));
                else if (content instanceof Kodo) {
                    content.each(function() {
                        self.appendChild(this.cloneNode(true));
                    });
                }
            });
            return this;
        },

        appendTo: function(target) {
            var $target = $(target);
            var nodes = [];
            this.each(function() {
                nodes.push(this.cloneNode(true));
            });
            $target.each(function() {
                var self = this;
                for (var i = 0; i < nodes.length; i++) {
                    self.appendChild(nodes[i].cloneNode(true));
                }
            });
            return this;
        },

        after: function(content) {
            if (typeof content === 'string') {
                this.each(function() { this.insertAdjacentHTML('afterend', content); });
                return this;
            }
            var nodes = parseContentToNodes(content);
            if (nodes.length === 0) return this;
            this.each(function() {
                var parent = this.parentNode;
                if (!parent) return;
                var nextSibling = this.nextSibling;
                var lastInserted = null;
                for (var i = 0; i < nodes.length; i++) {
                    var newNode = nodes[i].cloneNode(true);
                    if (lastInserted === null) parent.insertBefore(newNode, nextSibling);
                    else parent.insertBefore(newNode, lastInserted.nextSibling);
                    lastInserted = newNode;
                }
            });
            return this;
        },

        before: function(content) {
            if (typeof content === 'string') {
                this.each(function() { this.insertAdjacentHTML('beforebegin', content); });
                return this;
            }
            var nodes = parseContentToNodes(content);
            if (nodes.length === 0) return this;
            this.each(function() {
                var parent = this.parentNode;
                if (!parent) return;
                for (var i = nodes.length - 1; i >= 0; i--) {
                    parent.insertBefore(nodes[i].cloneNode(true), this);
                }
            });
            return this;
        },

        not: function(selector) {
            var filtered = [];
            this.each(function() {
                var match = false;
                if (typeof selector === 'string') {
                    var matchesFn = getMatchesMethod(this);
                    if (matchesFn && matchesFn.call(this, selector)) match = true;
                } else if (selector.nodeType) match = (this === selector);
                else if (selector instanceof Kodo) match = selector.elements.indexOf(this) !== -1;
                if (!match) filtered.push(this);
            });
            return Kodo(filtered);
        },

        // ---------- 事件绑定 ----------
        on: function(eventType, callback) {
            this.each(function() {
                var el = this;
                var handler = function(e) {
                    e = normalizeEvent(e);
                    var result = callback.call(el, e);
                    if (result === false) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                    }
                };
                if (!el._kodoHandlers) el._kodoHandlers = {};
                if (!el._kodoHandlers[eventType]) el._kodoHandlers[eventType] = [];
                el._kodoHandlers[eventType].push({ callback: callback, handler: handler });
                addEvent(el, eventType, handler, false);
            });
            return this;
        },

        off: function(eventType, callback) {
            this.each(function() {
                var el = this;
                if (!el._kodoHandlers || !el._kodoHandlers[eventType]) return;
                var list = el._kodoHandlers[eventType];
                for (var i = list.length - 1; i >= 0; i--) {
                    if (!callback || list[i].callback === callback) {
                        removeEvent(el, eventType, list[i].handler, false);
                        list.splice(i, 1);
                    }
                }
            });
            return this;
        },

        // ---------- 事件委托 ----------
        live: function(eventType, callback) {
            var liveSelector = this._selector;
            if (!liveSelector || /^<.*>$/.test(liveSelector.trim())) {
                if (this.elements.length) {
                    var first = this.elements[0];
                    if (first.id) liveSelector = '#' + first.id;
                    else if (first.className) liveSelector = '.' + first.className.split(' ')[0];
                    else liveSelector = first.tagName.toLowerCase();
                } else {
                    if (window.console && console.warn) {
                        console.warn('Kodo.live: cannot determine a valid selector for delegation');
                    }
                    return this;
                }
            }

            if (eventType === 'hover') {
                var enterFn, leaveFn;
                if (arguments.length >= 3) {
                    enterFn = callback;
                    leaveFn = arguments[2];
                } else if (arguments.length === 2) {
                    enterFn = leaveFn = callback;
                } else {
                    return this;
                }
                this._registerLive('mouseenter', enterFn, liveSelector);
                this._registerLive('mouseleave', leaveFn, liveSelector);
                return this;
            }

            this._registerLive(eventType, callback, liveSelector);
            return this;
        },

        _registerLive: function(eventType, callback, selector) {
            var noBubbleEvents = ['blur', 'focus', 'load', 'unload'];
            var useCapture = (noBubbleEvents.indexOf(eventType) !== -1);

            liveDelegates.push({
                selector: selector,
                callback: callback,
                eventType: eventType,
                useCapture: useCapture,
                specificity: getSpecificity(selector)
            });

            var globalKey = eventType + '|' + useCapture;
            if (!globalListenerRegistered[globalKey]) {
                if (eventType === 'mouseenter') {
                    addEvent(document, 'mouseover', handleGlobalMouseEnter, useCapture);
                } else if (eventType === 'mouseleave') {
                    addEvent(document, 'mouseout', handleGlobalMouseLeave, useCapture);
                } else {
                    addEvent(document, eventType, handleGlobalEvent, useCapture);
                }
                globalListenerRegistered[globalKey] = true;
            }
        },

        // ---------- 事件触发器 ----------
        click: function(callback) {
            if (callback === undefined) {
                this.each(function() {
                    if (this.click) this.click();
                });
                return this;
            } else {
                return this.on('click', callback);
            }
        },

        change: function(callback) {
            if (callback === undefined) {
                this.each(function() {
                    triggerKeyEvent(this, 'change');
                });
                return this;
            } else {
                return this.on('change', callback);
            }
        },

        keydown: function(callback) {
            if (callback === undefined) {
                this.each(function() {
                    triggerKeyEvent(this, 'keydown', 13);
                });
                return this;
            }
            return this.on('keydown', callback);
        },

        keyup: function(callback) {
            if (callback === undefined) {
                this.each(function() {
                    triggerKeyEvent(this, 'keyup', 13);
                });
                return this;
            }
            return this.on('keyup', callback);
        },

        mouseenter: function(callback) {
            if (callback === undefined) return this;
            return this.on('mouseenter', callback);
        },

        mouseleave: function(callback) {
            if (callback === undefined) return this;
            return this.on('mouseleave', callback);
        },

        focus: function(callback) {
            if (callback === undefined) {
                this.each(function() { if (this.focus) this.focus(); });
                return this;
            }
            return this.on('focus', callback);
        },

        blur: function(callback) {
            if (callback === undefined) {
                this.each(function() { if (this.blur) this.blur(); });
                return this;
            }
            return this.on('blur', callback);
        },

        ready: function(fn) {
            if (this.elements[0] === document) Kodo.ready(fn);
            else if (typeof fn === 'function') Kodo.ready(fn);
            return this;
        },

        serialize: function() {
            var params = this.serializeArray();
            var result = [];
            for (var i = 0; i < params.length; i++) {
                result.push(encodeURIComponent(params[i].name) + '=' + encodeURIComponent(params[i].value));
            }
            return result.join('&');
        },

        serializeArray: function() {
            var result = [];
            var elements = [];
            this.each(function() {
                if (this.nodeName === 'FORM') {
                    for (var i = 0; i < this.elements.length; i++) elements.push(this.elements[i]);
                } else if (this.nodeName === 'INPUT' || this.nodeName === 'SELECT' || this.nodeName === 'TEXTAREA') {
                    elements.push(this);
                }
            });
            var unique = [];
            for (var i = 0; i < elements.length; i++) {
                if (unique.indexOf(elements[i]) === -1) unique.push(elements[i]);
            }
            for (var i = 0; i < unique.length; i++) {
                var el = unique[i];
                if (el.disabled) continue;
                var name = el.name;
                if (!name) continue;
                var tag = el.nodeName.toUpperCase();
                if (tag === 'INPUT') {
                    var type = (el.type || '').toLowerCase();
                    if (type === 'checkbox' || type === 'radio') {
                        if (!el.checked) continue;
                        result.push({ name: name, value: el.value || 'on' });
                    } else if (type !== 'file') {
                        result.push({ name: name, value: el.value });
                    }
                } else if (tag === 'SELECT') {
                    for (var j = 0; j < el.options.length; j++) {
                        var opt = el.options[j];
                        if (opt.selected && (!el.multiple || !opt.disabled)) {
                            result.push({ name: name, value: opt.value });
                        }
                    }
                } else if (tag === 'TEXTAREA') {
                    result.push({ name: name, value: el.value });
                }
            }
            return result;
        },

        is: function(selector) {
            if (this.length === 0) return false;
            var el = this.elements[0];
            if (selector === ':checked') {
                if (el.type === 'checkbox' || el.type === 'radio') return !!el.checked;
                if (el.tagName === 'OPTION') return !!el.selected;
                return false;
            }
            var matchesFn = getMatchesMethod(el);
            if (matchesFn) return matchesFn.call(el, selector);
            return false;
        }
    };

    // ---------- 触发键盘事件的辅助 ----------
    function triggerKeyEvent(el, type, keyCode) {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(type, true, true);
        evt.keyCode = keyCode || 0;
        evt.which = keyCode || 0;
        el.dispatchEvent(evt);
    }

    // ---------- 文档就绪 ----------
    var domReadyDone = false;
    var readyCallbacks = [];

    function domReady() {
        if (domReadyDone) return;
        domReadyDone = true;
        var cbs = readyCallbacks.slice();
        readyCallbacks = [];
        for (var i = 0; i < cbs.length; i++) setTimeout(cbs[i], 0);
    }

    function bindReady() {
        if (document.readyState === 'complete') {
            setTimeout(domReady, 0);
            return;
        }
        document.addEventListener('DOMContentLoaded', domReady, false);
        window.addEventListener('load', domReady, false);
    }

    if (document.readyState === 'loading') bindReady();
    else setTimeout(domReady, 0);

    Kodo.ready = function(fn) {
        if (domReadyDone) setTimeout(fn, 0);
        else readyCallbacks.push(fn);
    };

    // ---------- Ajax ----------
    function createXHR() {
        return new XMLHttpRequest();
    }

    Kodo.ajax = function(options) {
        var xhr = createXHR();
        var url = options.url || '';
        var type = (options.type || 'GET').toUpperCase();
        var async = options.async !== false;
        var timeout = options.timeout || 0;
        var dataType = options.dataType || '';
        var success = options.success || function() {};
        var error = options.error || function() {};
        var complete = options.complete || function() {};
        var data = options.data || null;
        var context = options.context || xhr;
        var cache = options.cache !== undefined ? options.cache : true;
        var isFormData = (typeof FormData !== 'undefined' && data instanceof FormData);

        if (type === 'GET' && data) {
            if (isFormData) {
                if (window.console && console.warn) {
                    console.warn('$.ajax: GET request with FormData is not supported, data ignored.');
                }
                data = null;
            } else {
                var query = [];
                if (typeof data === 'object') {
                    for (var key in data) {
                        if (data.hasOwnProperty(key)) {
                            query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                        }
                    }
                    data = query.join('&');
                }
                if (data) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + data;
                    data = null;
                }
            }
        }

        if (type === 'POST' && data && !isFormData) {
            if (typeof data === 'object') {
                var body = [];
                for (var k in data) {
                    if (data.hasOwnProperty(k)) {
                        body.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
                    }
                }
                data = body.join('&');
            }
        }

        if (cache === false && type === 'GET') {
            var separator = url.indexOf('?') === -1 ? '?' : '&';
            if (url.indexOf('_=') === -1) {
                url += separator + '_=' + (+new Date());
            }
        }

        xhr.open(type, url, async);
        if (type === 'POST' && data && typeof data === 'string' && !isFormData) {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        }
        if (timeout && async && 'timeout' in xhr) xhr.timeout = timeout;

        var done = false;
        function onComplete() {
            if (done) return;
            done = true;
            var status = xhr.status;
            if (status === 1223) status = 204;
            if (status >= 200 && status < 300) {
                var response = xhr.responseText;
                if (dataType === 'json') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        error.call(context, xhr, 'parseerror', e);
                        complete.call(context, xhr, 'error');
                        return;
                    }
                }
                success.call(context, response, status, xhr);
                complete.call(context, xhr, 'success');
            } else {
                error.call(context, xhr, xhr.statusText || 'error', status);
                complete.call(context, xhr, 'error');
            }
        }

        xhr.onload = onComplete;
        xhr.onerror = function() {
            if (done) return;
            done = true;
            error.call(context, xhr, 'Network Error', 0);
            complete.call(context, xhr, 'error');
        };
        xhr.ontimeout = function() {
            if (done) return;
            done = true;
            error.call(context, xhr, 'timeout', 0);
            complete.call(context, xhr, 'timeout');
        };

        xhr.send(data);
    };

    // ---------- 全局导出 ----------
    var $ = function(selector, context) {
        var instance = new Kodo(selector, context);
        if (typeof selector === 'string') instance._selector = selector;
        return instance;
    };
    $.ajax = Kodo.ajax;
    $.ready = Kodo.ready;
    $.fn = $.prototype = Kodo.prototype;
    for (var prop in Kodo) {
        if (Kodo.hasOwnProperty(prop) && !(prop in $)) $[prop] = Kodo[prop];
    }
    window.$ = window.Kodo = $;


    /* ==========================================================
     * 第二部分：laydate.js (Chrome 79+ 兼容版)
     * ========================================================== */

    var currentPanel = null;
    var currentInput = null;
    var currentConfig = null;
    var currentDateTime = null;
    var currentYear, currentMonth;

    // Chrome 79+ 原生支持 WeakMap
    var configMap = new WeakMap();

    // 年份范围（前后各50年）
    var YEAR_RANGE = 50;

    function padZero(num) {
        return num < 10 ? '0' + num : '' + num;
    }

    function formatDateTime(date, formatStr) {
        if (!date || !(date instanceof Date) || isNaN(date.getTime())) return '';
        var year = date.getFullYear();
        var month = padZero(date.getMonth() + 1);
        var day = padZero(date.getDate());
        var hour = padZero(date.getHours());
        var minute = padZero(date.getMinutes());
        var second = padZero(date.getSeconds());
        return formatStr
            .replace('yyyy', year)
            .replace('MM', month)
            .replace('dd', day)
            .replace('HH', hour)
            .replace('mm', minute)
            .replace('ss', second);
    }

    function parseDateTime(str, formatStr) {
        if (!str || typeof str !== 'string') return null;
        var pattern = formatStr
            .replace(/yyyy/g, '(\\d{4})')
            .replace(/MM/g, '(\\d{1,2})')
            .replace(/dd/g, '(\\d{1,2})')
            .replace(/HH/g, '(\\d{1,2})')
            .replace(/mm/g, '(\\d{1,2})')
            .replace(/ss/g, '(\\d{1,2})');
        var regex = new RegExp('^' + pattern + '$');
        var match = str.match(regex);
        if (!match) return null;
        var idx = 1;
        var getVal = function() { return parseInt(match[idx++], 10); };
        var year, month, day, hour = 0, minute = 0, second = 0;
        var parts = [];
        if (formatStr.indexOf('yyyy') !== -1) parts.push('year');
        if (formatStr.indexOf('MM') !== -1) parts.push('month');
        if (formatStr.indexOf('dd') !== -1) parts.push('day');
        if (formatStr.indexOf('HH') !== -1) parts.push('hour');
        if (formatStr.indexOf('mm') !== -1) parts.push('minute');
        if (formatStr.indexOf('ss') !== -1) parts.push('second');
        for (var i = 0; i < parts.length; i++) {
            var val = getVal();
            if (parts[i] === 'year') year = val;
            else if (parts[i] === 'month') month = val;
            else if (parts[i] === 'day') day = val;
            else if (parts[i] === 'hour') hour = val;
            else if (parts[i] === 'minute') minute = val;
            else if (parts[i] === 'second') second = val;
        }
        if (year === undefined) return null;
        if (month === undefined) return null;
        if (day === undefined) day = 1;
        if (month < 1 || month > 12 || day < 1 || day > 31) return null;
        var date = new Date(year, month - 1, day, hour, minute, second);
        if (date.getFullYear() !== year || date.getMonth() !== month - 1) return null;
        if (formatStr.indexOf('dd') !== -1 && date.getDate() !== day) return null;
        return date;
    }

    function getDefaultDateTime(type) {
        var now = new Date();
        if (type === 'date') {
            return new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
        } else if (type === 'time') {
            return new Date(1970, 0, 1, now.getHours(), now.getMinutes(), 0);
        } else if (type === 'month') {
            return new Date(now.getFullYear(), now.getMonth(), 1, 0, 0, 0);
        } else {
            return new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes(), 0);
        }
    }

    function generateDaysHTML(year, month, config) {
        var firstDay = new Date(year, month - 1, 1);
        var startWeek = firstDay.getDay();
        var daysInMonth = new Date(year, month, 0).getDate();
        var prevMonthDays = new Date(year, month - 1, 0).getDate();
        var rows = [];
        var dayCount = 1;
        var nextMonthDay = 1;
        for (var i = 0; i < 6; i++) {
            var cells = [];
            for (var j = 0; j < 7; j++) {
                var cellDay = null;
                var isCurrent = true;
                var cellYear = year, cellMonth = month;
                if (i === 0 && j < startWeek) {
                    cellDay = prevMonthDays - startWeek + j + 1;
                    isCurrent = false;
                    cellYear = year;
                    cellMonth = month - 1;
                    if (cellMonth < 1) { cellMonth = 12; cellYear--; }
                } else if (dayCount > daysInMonth) {
                    cellDay = nextMonthDay++;
                    isCurrent = false;
                    cellYear = year;
                    cellMonth = month + 1;
                    if (cellMonth > 12) { cellMonth = 1; cellYear++; }
                } else {
                    cellDay = dayCount++;
                    isCurrent = true;
                    cellYear = year;
                    cellMonth = month;
                }
                cells.push({ day: cellDay, current: isCurrent, year: cellYear, month: cellMonth });
            }
            rows.push(cells);
            if (dayCount > daysInMonth && nextMonthDay > 7) break;
        }

        var selectedDateOnly = currentDateTime ? new Date(currentDateTime.getFullYear(), currentDateTime.getMonth(), currentDateTime.getDate()) : null;
        var html = '<div style="padding:4px;">';
        for (var r = 0; r < rows.length; r++) {
            html += '<div style="display:flex;">';
            for (var c = 0; c < rows[r].length; c++) {
                var cell = rows[r][c];
                var style = 'flex:1; text-align:center; padding:6px 0; cursor:pointer; font-size:12px;';
                style += cell.current ? 'color:#333;' : 'color:#ccc;';
                var today = new Date();
                if (cell.current && cell.year === today.getFullYear() && cell.month === today.getMonth()+1 && cell.day === today.getDate()) {
                    style += 'background-color:#e6f7ff; border-radius:2px;';
                }
                if (selectedDateOnly && cell.year === selectedDateOnly.getFullYear() && cell.month === selectedDateOnly.getMonth()+1 && cell.day === selectedDateOnly.getDate()) {
                    style += 'background-color:#1890ff; color:#fff;';
                }
                html += '<div style="'+style+'" data-year="'+cell.year+'" data-month="'+cell.month+'" data-day="'+cell.day+'" data-current="'+cell.current+'">'+cell.day+'</div>';
            }
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    function generateMonthsHTML(year, config) {
        var selectedYear = currentDateTime ? currentDateTime.getFullYear() : null;
        var selectedMonth = currentDateTime ? currentDateTime.getMonth() + 1 : null;
        var html = '<div style="padding:8px; display:flex; flex-wrap:wrap;">';
        for (var m = 1; m <= 12; m++) {
            var style = 'width:33.33%; text-align:center; padding:10px 0; cursor:pointer; font-size:13px; border-radius:2px;';
            if (year === selectedYear && m === selectedMonth) {
                style += 'background-color:#1890ff; color:#fff;';
            } else {
                style += 'color:#333;';
            }
            html += '<div style="'+style+'" data-month="'+m+'">'+m+'月</div>';
        }
        html += '</div>';
        return html;
    }

    function bindDateClickEvents(container) {
        var dayDivs = container.querySelectorAll('[data-day]');
        for (var i = 0; i < dayDivs.length; i++) {
            var div = dayDivs[i];
            div.onclick = (function(el) {
                return function(e) {
                    e.stopPropagation();
                    var y = parseInt(el.getAttribute('data-year'), 10);
                    var m = parseInt(el.getAttribute('data-month'), 10);
                    var d = parseInt(el.getAttribute('data-day'), 10);
                    var isCurrent = el.getAttribute('data-current') === 'true';
                    if (currentConfig.type === 'date' || currentConfig.type === 'datetime') {
                        if (!isCurrent) {
                            currentYear = y;
                            currentMonth = m;
                            refreshDaysPanel();
                        }
                        if (currentDateTime) {
                            currentDateTime.setFullYear(y, m-1, d);
                        } else {
                            currentDateTime = new Date(y, m-1, d, 0, 0, 0);
                        }
                        refreshDaysPanel();
                        updatePreviewText();
                        if (currentConfig.type === 'datetime') {
                            syncTimePickerFromDateTime();
                        }
                    }
                };
            })(div);
        }
    }

    function bindMonthClickEvents(container) {
        var monthDivs = container.querySelectorAll('[data-month]');
        for (var i = 0; i < monthDivs.length; i++) {
            var div = monthDivs[i];
            div.onclick = (function(el) {
                return function(e) {
                    e.stopPropagation();
                    var m = parseInt(el.getAttribute('data-month'), 10);
                    if (currentConfig.type === 'month') {
                        if (currentDateTime) {
                            currentDateTime.setFullYear(currentYear, m-1, 1);
                        } else {
                            currentDateTime = new Date(currentYear, m-1, 1, 0, 0, 0);
                        }
                        refreshMonthPanel();
                        updatePreviewText();
                    }
                };
            })(div);
        }
    }

    function updateYearSelect(yearSelect, currentYear) {
        if (!yearSelect) return;
        var currentValue = parseInt(yearSelect.value, 10);
        yearSelect.innerHTML = '';
        var startYear = currentYear - YEAR_RANGE;
        var endYear = currentYear + YEAR_RANGE;
        for (var y = startYear; y <= endYear; y++) {
            var opt = document.createElement('option');
            opt.value = y;
            opt.text = y + '年';
            if (y === currentYear) opt.selected = true;
            yearSelect.appendChild(opt);
        }
        if (currentValue >= startYear && currentValue <= endYear) {
            yearSelect.value = currentValue;
        }
    }

    function refreshDaysPanel() {
        if (!currentPanel || !currentConfig) return;
        var daysContainer = currentPanel.querySelector('.laydate-days-container');
        if (daysContainer) {
            daysContainer.innerHTML = generateDaysHTML(currentYear, currentMonth, currentConfig);
            bindDateClickEvents(daysContainer);
        }
        var yearSelect = currentPanel.querySelector('.laydate-year-select');
        if (yearSelect) updateYearSelect(yearSelect, currentYear);
        var monthSelect = currentPanel.querySelector('.laydate-month-select');
        if (monthSelect) monthSelect.value = currentMonth;
    }

    function refreshMonthPanel() {
        if (!currentPanel || !currentConfig) return;
        var monthContainer = currentPanel.querySelector('.laydate-month-container');
        if (monthContainer) {
            monthContainer.innerHTML = generateMonthsHTML(currentYear, currentConfig);
            bindMonthClickEvents(monthContainer);
        }
        var yearSelect = currentPanel.querySelector('.laydate-year-select');
        if (yearSelect) updateYearSelect(yearSelect, currentYear);
    }

    function updatePanel(year, month) {
        if (month < 1) { month = 12; year--; }
        else if (month > 12) { month = 1; year++; }
        if (year === currentYear && month === currentMonth) return;
        currentYear = year;
        currentMonth = month;
        if (currentConfig.type === 'month') {
            refreshMonthPanel();
        } else {
            refreshDaysPanel();
        }
    }

    function buildTimePicker(config) {
        var container = document.createElement('div');
        container.className = 'laydate-time-picker';
        container.style.cssText = 'padding:8px; border-top:1px solid #eee; display:flex; justify-content:center; align-items:center; gap:8px;';
        var showSeconds = config.format.indexOf('ss') !== -1;
        var hourSelect = document.createElement('select');
        hourSelect.className = 'laydate-hour-select';
        hourSelect.style.cssText = 'padding:4px; font-size:12px;';
        for (var h = 0; h <= 23; h++) {
            var opt = document.createElement('option');
            opt.value = h;
            opt.text = padZero(h);
            hourSelect.appendChild(opt);
        }
        var minuteSelect = document.createElement('select');
        minuteSelect.className = 'laydate-minute-select';
        minuteSelect.style.cssText = 'padding:4px; font-size:12px;';
        for (var m = 0; m <= 59; m++) {
            var optM = document.createElement('option');
            optM.value = m;
            optM.text = padZero(m);
            minuteSelect.appendChild(optM);
        }
        container.appendChild(hourSelect);
        container.appendChild(document.createTextNode(':'));
        container.appendChild(minuteSelect);
        var secondSelect = null;
        if (showSeconds) {
            secondSelect = document.createElement('select');
            secondSelect.className = 'laydate-second-select';
            secondSelect.style.cssText = 'padding:4px; font-size:12px;';
            for (var s = 0; s <= 59; s++) {
                var optS = document.createElement('option');
                optS.value = s;
                optS.text = padZero(s);
                secondSelect.appendChild(optS);
            }
            container.appendChild(document.createTextNode(':'));
            container.appendChild(secondSelect);
        }
        function updateTimeFromPicker() {
            if (!currentDateTime) return;
            var hour = parseInt(hourSelect.value, 10);
            var minute = parseInt(minuteSelect.value, 10);
            var second = secondSelect ? parseInt(secondSelect.value, 10) : 0;
            currentDateTime.setHours(hour, minute, second);
            updatePreviewText();
        }
        hourSelect.onchange = updateTimeFromPicker;
        minuteSelect.onchange = updateTimeFromPicker;
        if (secondSelect) secondSelect.onchange = updateTimeFromPicker;
        container._hourSelect = hourSelect;
        container._minuteSelect = minuteSelect;
        container._secondSelect = secondSelect;
        return container;
    }

    function syncTimePickerFromDateTime() {
        if (!currentPanel || !currentDateTime) return;
        var timePicker = currentPanel.querySelector('.laydate-time-picker');
        if (!timePicker) return;
        var hourSelect = timePicker._hourSelect;
        var minuteSelect = timePicker._minuteSelect;
        var secondSelect = timePicker._secondSelect;
        if (hourSelect) hourSelect.value = currentDateTime.getHours();
        if (minuteSelect) minuteSelect.value = currentDateTime.getMinutes();
        if (secondSelect) secondSelect.value = currentDateTime.getSeconds();
    }

    function updatePreviewText() {
        if (!currentPanel || !currentConfig || !currentDateTime) return;
        var previewSpan = currentPanel.querySelector('.laydate-preview');
        if (previewSpan) {
            previewSpan.textContent = formatDateTime(currentDateTime, currentConfig.format);
        }
    }

    function setDateTimeAndClose(dateTime, config) {
        if (!currentInput) return;
        var formatted = formatDateTime(dateTime, config.format);
        currentInput.value = formatted;
        currentDateTime = dateTime;
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent('change', true, true);
        currentInput.dispatchEvent(evt);
        hidePanel();
    }

    function onConfirm() {
        if (currentConfig && currentDateTime) {
            setDateTimeAndClose(currentDateTime, currentConfig);
        } else {
            hidePanel();
        }
    }

    function setToNow() {
        if (!currentConfig) return;
        var now = new Date();
        var type = currentConfig.type;
        if (type === 'date') {
            currentDateTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
        } else if (type === 'time') {
            if (!currentDateTime) currentDateTime = new Date(1970, 0, 1, 0, 0, 0);
            currentDateTime.setHours(now.getHours(), now.getMinutes(), now.getSeconds());
        } else if (type === 'month') {
            currentDateTime = new Date(now.getFullYear(), now.getMonth(), 1, 0, 0, 0);
        } else {
            currentDateTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes(), now.getSeconds());
        }
        if (type !== 'time') {
            currentYear = currentDateTime.getFullYear();
            currentMonth = currentDateTime.getMonth() + 1;
        }
        if (type === 'month') {
            refreshMonthPanel();
        } else if (type !== 'time') {
            refreshDaysPanel();
        }
        if (type === 'datetime' || type === 'time') {
            syncTimePickerFromDateTime();
        }
        updatePreviewText();
    }

    function createPanel(inputEl, config) {
        var panel = document.createElement('div');
        panel.className = 'laydate-panel';
        var baseWidth = (config.type === 'time') ? 220 : ((config.type === 'month') ? 260 : 260);
        panel.style.cssText = 'position:absolute; z-index:9999; background:#fff; border:1px solid #ccc; border-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,0.15); font-family:微软雅黑,宋体; font-size:14px; width:' + baseWidth + 'px; -webkit-user-select:none; user-select:none;';

        var header = document.createElement('div');
        header.style.cssText = 'padding:8px; border-bottom:1px solid #eee; text-align:center; position:relative;';
        var prevYearBtn = document.createElement('button');
        prevYearBtn.innerHTML = '«';
        prevYearBtn.style.cssText = 'position:absolute; left:8px; top:8px; border:none; background:none; cursor:pointer; font-size:14px;';
        prevYearBtn.onclick = function(e) { e.stopPropagation(); updatePanel(currentYear - 1, currentMonth); };
        var prevMonthBtn = null, nextMonthBtn = null;
        if (config.type !== 'month') {
            prevMonthBtn = document.createElement('button');
            prevMonthBtn.innerHTML = '‹';
            prevMonthBtn.style.cssText = 'position:absolute; left:32px; top:8px; border:none; background:none; cursor:pointer; font-size:14px;';
            prevMonthBtn.onclick = function(e) { e.stopPropagation(); updatePanel(currentYear, currentMonth - 1); };
            nextMonthBtn = document.createElement('button');
            nextMonthBtn.innerHTML = '›';
            nextMonthBtn.style.cssText = 'position:absolute; right:32px; top:8px; border:none; background:none; cursor:pointer; font-size:14px;';
            nextMonthBtn.onclick = function(e) { e.stopPropagation(); updatePanel(currentYear, currentMonth + 1); };
        }
        var nextYearBtn = document.createElement('button');
        nextYearBtn.innerHTML = '»';
        nextYearBtn.style.cssText = 'position:absolute; right:8px; top:8px; border:none; background:none; cursor:pointer; font-size:14px;';
        nextYearBtn.onclick = function(e) { e.stopPropagation(); updatePanel(currentYear + 1, currentMonth); };

        var yearSelect = document.createElement('select');
        yearSelect.className = 'laydate-year-select';
        yearSelect.style.cssText = 'margin:0 4px; padding:2px; font-size:14px;';
        updateYearSelect(yearSelect, currentYear);
        yearSelect.onchange = function() { updatePanel(parseInt(this.value, 10), currentMonth); };

        header.appendChild(prevYearBtn);
        if (prevMonthBtn) header.appendChild(prevMonthBtn);
        header.appendChild(yearSelect);
        if (config.type !== 'month') {
            var monthSelect = document.createElement('select');
            monthSelect.className = 'laydate-month-select';
            monthSelect.style.cssText = 'margin:0 4px; padding:2px; font-size:14px;';
            for (var m = 1; m <= 12; m++) {
                var optM = document.createElement('option');
                optM.value = m;
                optM.text = m + '月';
                if (m === currentMonth) optM.selected = true;
                monthSelect.appendChild(optM);
            }
            monthSelect.onchange = function() { updatePanel(currentYear, parseInt(this.value, 10)); };
            header.appendChild(monthSelect);
        }
        if (nextMonthBtn) header.appendChild(nextMonthBtn);
        header.appendChild(nextYearBtn);
        panel.appendChild(header);

        var previewRow = document.createElement('div');
        previewRow.style.cssText = 'padding:6px 8px; text-align:center; border-top:1px solid #eee; background:#fafafa; font-size:12px; color:#666;';
        var previewSpan = document.createElement('span');
        previewSpan.className = 'laydate-preview';
        previewRow.appendChild(previewSpan);
        panel.appendChild(previewRow);

        if (config.type === 'time') {
            var timePicker = buildTimePicker(config);
            panel.appendChild(timePicker);
        } else if (config.type === 'month') {
            var monthContainer = document.createElement('div');
            monthContainer.className = 'laydate-month-container';
            monthContainer.innerHTML = generateMonthsHTML(currentYear, config);
            panel.appendChild(monthContainer);
            bindMonthClickEvents(monthContainer);
        } else {
            var weekRow = document.createElement('div');
            weekRow.style.cssText = 'display:flex; border-bottom:1px solid #eee; background:#f5f5f5;';
            var weeks = ['日', '一', '二', '三', '四', '五', '六'];
            for (var w = 0; w < 7; w++) {
                var weekCell = document.createElement('div');
                weekCell.style.cssText = 'flex:1; text-align:center; padding:6px 0; font-size:12px;';
                weekCell.textContent = weeks[w];
                weekRow.appendChild(weekCell);
            }
            var daysContainer = document.createElement('div');
            daysContainer.className = 'laydate-days-container';
            daysContainer.innerHTML = generateDaysHTML(currentYear, currentMonth, config);
            panel.appendChild(weekRow);
            panel.appendChild(daysContainer);
            bindDateClickEvents(daysContainer);
            if (config.type === 'datetime') {
                var timePicker2 = buildTimePicker(config);
                panel.appendChild(timePicker2);
            }
        }

        var btnRow = document.createElement('div');
        btnRow.style.cssText = 'padding:8px; text-align:center; border-top:1px solid #eee;';

        var nowBtn = document.createElement('button');
        nowBtn.textContent = '现在';
        nowBtn.style.cssText = 'padding:4px 12px; background:#fff; color:#333; border:1px solid #d9d9d9; border-radius:2px; cursor:pointer; font-size:12px; margin-right:8px;';
        nowBtn.onclick = function(e) {
            e.stopPropagation();
            setToNow();
        };
        btnRow.appendChild(nowBtn);

        var confirmBtn = document.createElement('button');
        confirmBtn.textContent = '确定';
        confirmBtn.style.cssText = 'padding:4px 12px; background:#1890ff; color:#fff; border:none; border-radius:2px; cursor:pointer; font-size:12px;';
        confirmBtn.onclick = onConfirm;
        btnRow.appendChild(confirmBtn);

        panel.appendChild(btnRow);

        if (currentDateTime) {
            var previewSpan2 = panel.querySelector('.laydate-preview');
            if (previewSpan2) previewSpan2.textContent = formatDateTime(currentDateTime, config.format);
        }
        return panel;
    }

    function setPanelPosition(panel, inputEl) {
        var rect = inputEl.getBoundingClientRect();
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        var viewportWidth = window.innerWidth;
        var viewportHeight = window.innerHeight;
        var panelWidth = panel.offsetWidth;
        var panelHeight = panel.offsetHeight;
        var top = rect.bottom + scrollTop + 2;
        var left = rect.left + scrollLeft;
        var rightEdge = left + panelWidth;
        if (rightEdge > scrollLeft + viewportWidth) left = scrollLeft + viewportWidth - panelWidth - 2;
        if (left < scrollLeft) left = scrollLeft + 2;
        var bottomEdge = top + panelHeight;
        var viewportBottom = scrollTop + viewportHeight;
        if (bottomEdge > viewportBottom) {
            top = rect.top + scrollTop - panelHeight - 2;
            if (top < scrollTop) top = scrollTop + 2;
        }
        panel.style.top = top + 'px';
        panel.style.left = left + 'px';
    }

    function hidePanel() {
        if (currentPanel && currentPanel.parentNode) {
            currentPanel.parentNode.removeChild(currentPanel);
        }
        currentPanel = null;
        currentInput = null;
        currentConfig = null;
        document.removeEventListener('click', documentClickHandler);
    }

    function documentClickHandler(e) {
        if (!currentPanel) return;
        var target = e.target;

        // 点击面板内部：不关闭
        if (currentPanel === target || currentPanel.contains(target)) return;

        // 点击触发输入框：不关闭
        if (currentInput === target || (currentInput && currentInput.contains && currentInput.contains(target))) return;

        // 点击 laydate 相关元素：不关闭（防止 hst.js 委托干扰）
        if (isLaydateElement(target)) return;

        hidePanel();
    }

    function show(inputEl, config) {
        if (currentPanel) hidePanel();
        currentInput = inputEl;
        currentConfig = config;

        var val = inputEl.value;
        var parsed = parseDateTime(val, config.format);
        if (parsed && parsed instanceof Date && !isNaN(parsed.getTime())) {
            currentDateTime = parsed;
        } else {
            currentDateTime = getDefaultDateTime(config.type);
        }

        if (config.type !== 'time') {
            currentYear = currentDateTime.getFullYear();
            currentMonth = currentDateTime.getMonth() + 1;
        } else {
            currentYear = 2000;
            currentMonth = 1;
        }

        var panel = createPanel(inputEl, config);
        document.body.appendChild(panel);
        currentPanel = panel;

        if (config.type !== 'date' && config.type !== 'month') {
            syncTimePickerFromDateTime();
        }

        setPanelPosition(panel, inputEl);

        document.removeEventListener('click', documentClickHandler);
        setTimeout(function() {
            document.addEventListener('click', documentClickHandler);
        }, 0);
    }

    function laydate(selector) {
        if (typeof selector === 'string') {
            var elem = document.querySelector(selector);
            if (elem && elem.tagName === 'INPUT') {
                laydate.render({ elem: selector, type: 'date', format: 'yyyy-MM-dd' });
            } else if (elem) {
                console.warn('laydate: 只支持文本输入框');
            }
        } else if (selector && selector.nodeType === 1 && selector.tagName === 'INPUT') {
            laydate.render({ elem: selector, type: 'date', format: 'yyyy-MM-dd' });
        }
    }

    laydate.render = function(options) {
        if (!options || !options.elem) return;
        var inputElem;
        if (typeof options.elem === 'string') {
            inputElem = document.querySelector(options.elem);
        } else if (options.elem.nodeType === 1) {
            inputElem = options.elem;
        }
        if (!inputElem || inputElem.tagName !== 'INPUT') return;

        var type = options.type || 'date';
        var defaultFormat;
        if (type === 'date') defaultFormat = 'yyyy-MM-dd';
        else if (type === 'time') defaultFormat = 'HH:mm';
        else if (type === 'month') defaultFormat = 'yyyy-MM';
        else defaultFormat = 'yyyy-MM-dd HH:mm';

        var format = options.format || defaultFormat;
        if (type === 'date' && (format.indexOf('HH') !== -1 || format.indexOf('mm') !== -1)) format = 'yyyy-MM-dd';
        if (type === 'time' && format.indexOf('yyyy') !== -1) format = 'HH:mm';
        if (type === 'month' && format.indexOf('dd') !== -1) format = 'yyyy-MM';

        var config = { type: type, format: format };

        // 先更新配置（关键：手动 render 时能覆盖旧配置）
        configMap.set(inputElem, config);

        // 已经绑定过：只更新配置，不重复绑定
        if (inputElem._laydateBound) return;
        inputElem._laydateBound = true;

        inputElem.addEventListener('click', function(e) {
            e.stopPropagation();
            var cfg = configMap.get(inputElem);
            if (cfg) show(inputElem, cfg);
        });
    };

    // 页面加载时自动初始化 class="laydate" 的 input
    function autoInit() {
        var inputs = document.querySelectorAll('input.laydate');
        for (var i = 0; i < inputs.length; i++) laydate(inputs[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    window.laydate = laydate;
})(window);