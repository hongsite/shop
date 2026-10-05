/**
 * hst.js - 轻量级 DOM 操作库 (终极增强版 + 老浏览器兼容)
 * 修复 HTML 字符串被误当作选择器的问题
 * 支持 .live('hover', enterFn, leaveFn) 委托
 * 包含 offset()、outerWidth()、appendTo() 等常用方法
 * （移除了 fadeIn/fadeOut/fadeToggle）
 * 兼容 IE8+（事件对象归一化、attachEvent、ActiveXObject 等）
 */
(function(window, undefined) {
    'use strict';

    // ---------- 辅助函数 ----------
    function getMatchesMethod(el) {
        return el.matches || el.webkitMatchesSelector || el.msMatchesSelector ||
               el.mozMatchesSelector || el.oMatchesSelector;
    }

    function addEvent(el, type, handler, useCapture) {
        if (el.addEventListener) {
            el.addEventListener(type, handler, !!useCapture);
        } else if (el.attachEvent) {
            el.attachEvent('on' + type, handler);
        }
    }

    function removeEvent(el, type, handler, useCapture) {
        if (el.removeEventListener) {
            el.removeEventListener(type, handler, !!useCapture);
        } else if (el.detachEvent) {
            el.detachEvent('on' + type, handler);
        }
    }

    // 事件对象归一化
    function normalizeEvent(e) {
        e = e || window.event;
        if (e.which === undefined) {
            e.which = e.keyCode || e.charCode || 0;
        }
        if (e.keyCode === undefined) {
            e.keyCode = e.which;
        }
        if (e.target === undefined) {
            e.target = e.srcElement;
        }
        if (e.currentTarget === undefined) {
            e.currentTarget = e.srcElement;
        }
        if (!e.preventDefault) {
            e.preventDefault = function() { e.returnValue = false; };
        }
        if (!e.stopPropagation) {
            e.stopPropagation = function() { e.cancelBubble = true; };
        }
        if (!e.stopImmediatePropagation) {
            e.stopImmediatePropagation = function() {
                e.cancelBubble = true;
                e.returnValue = false;
            };
        }
        if (e.relatedTarget === undefined) {
            e.relatedTarget = e.fromElement || e.toElement;
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
            var eqIndex = parseInt(eqMatch[1], 10);
            filters.push({ type: 'eq', index: eqIndex });
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

    // 兼容老浏览器的 getComputedStyle
    function getComputedStyleSafe(el) {
        if (window.getComputedStyle) return window.getComputedStyle(el, null);
        return el.currentStyle || {};
    }

    function isPlainObject(obj) {
        return obj && typeof obj === 'object' && !Array.isArray(obj) && obj !== null && !obj.nodeType && !(obj instanceof Kodo);
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

    function handleGlobalEvent(e, overrideType) {
		e = normalizeEvent(e);
		var eventType = overrideType || e.type;
		var target = e.target;

		// 如果点击目标是 laydate 输入框或面板内部，交给 laydate 自己处理，不拦截
		if (eventType === 'click') {
			var el = target;
			while (el && el !== document) {
				if (el.className && typeof el.className === 'string') {
					if (el.className.indexOf('laydate') !== -1) {
						return;
					}
				}
				el = el.parentNode;
			}
		}

		var matched = [];

		for (var i = 0; i < liveDelegates.length; i++) {
			var d = liveDelegates[i];
			if (d.eventType !== eventType) continue;
			var el2 = target;
			while (el2 && el2 !== document) {
				var matchesFn = getMatchesMethod(el2);
				var hit = false;
				if (matchesFn) {
					hit = matchesFn.call(el2, d.selector);
				}
				if (hit) {
					matched.push({ delegate: d, element: el2 });
					break;
				}
				el2 = el2.parentNode;
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
		// 从子元素移入自身，不算 enter
		if (related && target && contains(target, related)) return;
		handleGlobalEvent(e, 'mouseenter');
	}

	function handleGlobalMouseLeave(e) {
		e = normalizeEvent(e);
		var target = e.target;
		var related = e.relatedTarget;
		// 从自身移入子元素，不算 leave
		if (related && target && contains(target, related)) return;
		handleGlobalEvent(e, 'mouseleave');
	}

    function contains(parent, child) {
        if (!parent || !child) return false;
        if (parent.contains) return parent.contains(child);
        while (child) {
            if (child === parent) return true;
            child = child.parentNode;
        }
        return false;
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
            // 检测是否为 HTML 字符串（以 < 开头，包含 >）
            if (trimmed.indexOf('<') === 0 && trimmed.indexOf('>') > 0) {
                var div = document.createElement('div');
                div.innerHTML = trimmed;
                this.elements = Array.prototype.slice.call(div.childNodes);
                return this;
            }
            // 否则作为 CSS 选择器
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
                this.each(function() { texts.push(this.textContent || this.innerText || ''); });
                return texts.join('');
            }
            this.each(function() {
                if (this.textContent !== undefined) this.textContent = content;
                else this.innerText = content;
            });
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
            this.each(function() {
                if (this.classList) this.classList.add(className);
                else {
                    var classes = (this.className || '').split(/\s+/);
                    if (classes.indexOf(className) === -1) {
                        this.className = (this.className ? this.className + ' ' : '') + className;
                    }
                }
            });
            return this;
        },

        removeClass: function(className) {
            this.each(function() {
                if (this.classList) this.classList.remove(className);
                else {
                    var classes = (this.className || '').split(/\s+/);
                    var result = [];
                    for (var i = 0; i < classes.length; i++) {
                        if (classes[i] && classes[i] !== className) result.push(classes[i]);
                    }
                    this.className = result.join(' ');
                }
            });
            return this;
        },

        toggleClass: function(className) {
            this.each(function() {
                if (this.classList) this.classList.toggle(className);
                else {
                    var classes = (this.className || '').split(/\s+/);
                    var idx = classes.indexOf(className);
                    if (idx !== -1) {
                        classes.splice(idx, 1);
                    } else {
                        classes.push(className);
                    }
                    this.className = classes.join(' ');
                }
            });
            return this;
        },

        hasClass: function(className) {
            if (this.length === 0) return false;
            var el = this.elements[0];
            if (!el) return false;
            if (el.classList) return el.classList.contains(className);
            var classNames = (el.className || '').split(/\s+/);
            for (var i = 0; i < classNames.length; i++) {
                if (classNames[i] === className) return true;
            }
            return false;
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

        // ---------- 事件绑定（兼容 IE8）----------
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
                // 保存 handler 以便 off 使用
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

        // ---------- 事件委托（支持 hover，兼容 IE8）----------
        live: function(eventType, callback) {
            var liveSelector = this._selector;
            // 如果选择器不存在或为 HTML 字符串，则从元素生成
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

            // 处理 hover
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

            // 普通事件
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
                    else {
                        var evt = document.createEvent('MouseEvents');
                        evt.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
                        this.dispatchEvent(evt);
                    }
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
                    triggerKeyEvent(this, 'keydown', 13); // 默认回车
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

    // ---------- 触发键盘事件的兼容辅助 ----------
    function triggerKeyEvent(el, type, keyCode) {
        var evt;
        if (document.createEvent) {
            evt = document.createEvent('HTMLEvents');
            evt.initEvent(type, true, true);
            evt.keyCode = keyCode || 0;
            evt.which = keyCode || 0;
            el.dispatchEvent(evt);
        } else if (document.createEventObject) {
            // IE8
            evt = document.createEventObject();
            evt.keyCode = keyCode || 0;
            evt.which = keyCode || 0;
            evt.type = type;
            el.fireEvent('on' + type, evt);
        }
    }

    // ---------- 文档就绪（兼容 IE8）----------
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
        if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', domReady, false);
            window.addEventListener('load', domReady, false);
        } else if (document.attachEvent) {
            // IE8
            document.attachEvent('onreadystatechange', function() {
                if (document.readyState === 'complete') domReady();
            });
            window.attachEvent('onload', domReady);
            // 兜底：doScroll 轮询
            try {
                if (document.documentElement.doScroll && window === window.top) {
                    (function poll() {
                        if (domReadyDone) return;
                        try {
                            document.documentElement.doScroll('left');
                        } catch (e) {
                            setTimeout(poll, 50);
                            return;
                        }
                        domReady();
                    })();
                }
            } catch (e) {}
        }
    }

    if (document.readyState === 'loading') bindReady();
    else setTimeout(domReady, 0);

    Kodo.ready = function(fn) {
        if (domReadyDone) setTimeout(fn, 0);
        else readyCallbacks.push(fn);
    };

    // ---------- Ajax（兼容 IE8）----------
    function createXHR() {
        if (window.XMLHttpRequest) return new XMLHttpRequest();
        if (window.ActiveXObject) {
            try { return new ActiveXObject('Msxml2.XMLHTTP'); } catch (e) {}
            try { return new ActiveXObject('Microsoft.XMLHTTP'); } catch (e) {}
        }
        return null;
    }

    Kodo.ajax = function(options) {
        var xhr = createXHR();
        if (!xhr) {
            if (options.error) options.error(null, 'No XHR support', 0);
            return;
        }
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
            try {
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            } catch (e) {}
        }
        if (timeout && async && 'timeout' in xhr) xhr.timeout = timeout;

        // 兼容老浏览器：用 onreadystatechange
        var done = false;
        function onComplete() {
            if (done) return;
            done = true;
            var status;
            try { status = xhr.status; } catch (e) { status = 0; }
            // IE 缓存问题：status 1223 视为 204
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

        if (xhr.onload !== undefined && 'onload' in xhr) {
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
        } else {
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) onComplete();
            };
        }

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
})(window);