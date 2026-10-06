/**
 * 适配 hst.js(Kodo) 的 sortable 拖拽排序（丝滑版·最终）
 *
 * 特性：
 *  - 拖动克隆体用 transform 跟随，不触发 layout
 *  - 其它元素用 FLIP 动画平滑让位
 *  - 占位符移动判断稳定，支持任意数量元素（含 2 个）
 *  - rAF 节流 mousemove
 *  - 事件：sortstart、sort、sortstop (e, ui)
 *
 * 用法：
 *   var $box = window.Kodo('#mainpicul').sortable({
 *       items: '.lilist',
 *       placeholder: 'placeholder',
 *       threshold: 3,
 *       animation: 180
 *   });
 *   $box.on('sortstart', function (e, ui) {});
 *   $box.on('sort',      function (e, ui) {});
 *   $box.on('sortstop',  function (e, ui) {});
 */
(function () {
    'use strict';

    var $ = window.Kodo || window.$;
    if (!$ || !$.fn) {
        if (window.console && console.error) {
            console.error('[sortable] 未找到 Kodo($)，请确认 hst.js 已先于本插件加载');
        }
        return;
    }

    // ---------- 工具 ----------
    function getMatchesMethod(el) {
        return el.matches || el.webkitMatchesSelector || el.msMatchesSelector ||
               el.mozMatchesSelector || el.oMatchesSelector;
    }

    function matches(el, selector) {
        if (!el || !selector) return false;
        var fn = getMatchesMethod(el);
        if (!fn) return false;
        try { return fn.call(el, selector); } catch (e) { return false; }
    }

    function normalizeItems(selector) {
        if (typeof selector !== 'string') return '*';
        var s = selector.trim().replace(/^>\s*/, '');
        return s || '*';
    }

    function raf(fn) {
        if (window.requestAnimationFrame) return window.requestAnimationFrame(fn);
        return setTimeout(fn, 16);
    }
    function caf(id) {
        if (window.cancelAnimationFrame) return window.cancelAnimationFrame(id);
        clearTimeout(id);
    }

    // ---------- 自定义事件 ----------
    var eventMap = new WeakMap();

    function getEventStore($el) {
        var dom = $el.get(0);
        if (!dom) return {};
        if (!eventMap.has(dom)) eventMap.set(dom, {});
        return eventMap.get(dom);
    }
    function bindCustomEvent($c, name, fn) {
        var s = getEventStore($c);
        if (!s[name]) s[name] = [];
        s[name].push(fn);
    }
    function unbindCustomEvent($c, name, fn) {
        var s = getEventStore($c);
        if (!s[name]) return;
        if (!fn) { s[name] = []; return; }
        for (var i = s[name].length - 1; i >= 0; i--) {
            if (s[name][i] === fn) s[name].splice(i, 1);
        }
    }
    function triggerCustom($c, name, ev, ui) {
        var s = getEventStore($c);
        var list = s[name];
        if (!list || !list.length) return;
        list = list.slice();
        for (var i = 0; i < list.length; i++) {
            try { list[i].call($c.get(0), ev, ui); }
            catch (e) { if (window.console) console.error(e); }
        }
    }

    var CUSTOM_EVENTS = ['sortstart', 'sort', 'sortstop'];
    var originalOn = $.fn.on;
    var originalOff = $.fn.off;

    $.fn.on = function (t, cb) {
        if (CUSTOM_EVENTS.indexOf(t) !== -1) { bindCustomEvent(this, t, cb); return this; }
        return originalOn.call(this, t, cb);
    };
    $.fn.off = function (t, cb) {
        if (CUSTOM_EVENTS.indexOf(t) !== -1) { unbindCustomEvent(this, t, cb); return this; }
        return originalOff.call(this, t, cb);
    };

    // ---------- 主插件 ----------
    $.fn.sortable = function (options) {
        var defaults = {
            items: '> *',
            placeholder: 'placeholder',
            cursor: 'move',
            cancel: 'input,select,option,textarea,button,a',
            threshold: 3,
            connectWith: false,
            cloneClass: '',
            axis: 'auto',
            animation: 180,
            easing: 'cubic-bezier(0.2, 0, 0, 1)'
        };

        var opts = {};
        for (var k in defaults) {
            if (defaults.hasOwnProperty(k)) {
                opts[k] = (options && options[k] !== undefined) ? options[k] : defaults[k];
            }
        }

        return this.each(function () {
            var container = this;
            var $container = $(container);
            if (container._sortableInitialized) return;
            container._sortableInitialized = true;

            var itemSelector = normalizeItems(opts.items);

            var dragState = {
                mouseDownX: 0,
                mouseDownY: 0,
                draggedItem: null,
                cloneElement: null,
                placeholderElement: null,
                thresholdMet: false,
                offsetX: 0,
                offsetY: 0,
                rafId: 0,
                lastEvent: null
            };

            // ---------- 查询 ----------
            // 注意：排除被拖元素和占位符
            function getSortableItems() {
                var list = [];
                var all;
                try { all = container.querySelectorAll(itemSelector); }
                catch (e) { all = container.querySelectorAll('*'); }
                for (var i = 0; i < all.length; i++) {
                    var n = all[i];
                    if (n.parentNode !== container) continue;
                    if (n._isPlaceholder) continue;
                    if (n === dragState.draggedItem) continue;
                    list.push(n);
                }
                return list;
            }

            // 用于 FLIP：包含占位符，但不含被拖元素
            function getFlipItems() {
                var list = getSortableItems();
                if (dragState.placeholderElement) list.push(dragState.placeholderElement);
                return list;
            }

            function isSortableItem(el) {
                if (!el || el === container) return false;
                if (el.parentNode !== container) return false;
                return matches(el, itemSelector);
            }

            function isCancelElement(el) {
                if (!opts.cancel || !el) return false;
                var cur = el;
                while (cur && cur !== container) {
                    if (matches(cur, opts.cancel)) return true;
                    cur = cur.parentNode;
                }
                return false;
            }

            // ---------- 样式 ----------
            function disableSelect() {
                document.body.style.userSelect = 'none';
                document.body.style.webkitUserSelect = 'none';
                document.body.style.MozUserSelect = 'none';
                document.body.style.msUserSelect = 'none';
            }
            function enableSelect() {
                document.body.style.userSelect = '';
                document.body.style.webkitUserSelect = '';
                document.body.style.MozUserSelect = '';
                document.body.style.msUserSelect = '';
            }

            // ---------- FLIP ----------
            function recordRects() {
                var map = new Map();
                var items = getFlipItems();
                for (var i = 0; i < items.length; i++) {
                    map.set(items[i], items[i].getBoundingClientRect());
                }
                return map;
            }

            function playFlip(oldRects) {
                if (!opts.animation) return;
                var items = getFlipItems();
                for (var i = 0; i < items.length; i++) {
                    var el = items[i];
                    var oldRect = oldRects.get(el);
                    if (!oldRect) continue;
                    var newRect = el.getBoundingClientRect();
                    var dx = oldRect.left - newRect.left;
                    var dy = oldRect.top - newRect.top;
                    if (dx === 0 && dy === 0) continue;

                    el.style.transition = 'none';
                    el.style.transform = 'translate3d(' + dx + 'px,' + dy + 'px,0)';
                    void el.offsetWidth; // 强制 reflow
                    el.style.transition = 'transform ' + opts.animation + 'ms ' + opts.easing;
                    el.style.transform = '';
                }
            }

            // ---------- 占位符 / 克隆体 ----------
            function createPlaceholder(item) {
                var ph = document.createElement(item.tagName);
                ph._isPlaceholder = true;
                ph.className = opts.placeholder;
                var rect = item.getBoundingClientRect();
                ph.style.width = rect.width + 'px';
                ph.style.height = rect.height + 'px';
                ph.style.display = getComputedStyle(item).display;
                ph.style.boxSizing = 'border-box';
                // 占位符自身的移动交给 FLIP，不加 transition 避免冲突
                return ph;
            }

            function copyLayout(source, target) {
                var cs = window.getComputedStyle(source);
                var props = ['display','boxSizing','padding','margin','borderWidth',
                             'fontSize','lineHeight','textAlign','fontFamily','fontWeight',
                             'color','background'];
                for (var i = 0; i < props.length; i++) {
                    var v = cs.getPropertyValue(props[i]);
                    if (v) target.style.setProperty(props[i], v);
                }
                var r = source.getBoundingClientRect();
                target.style.width = r.width + 'px';
                target.style.height = r.height + 'px';
            }

            function createDragClone(source) {
                var clone = source.cloneNode(true);
                if (clone.id) clone.removeAttribute('id');
                if (opts.cloneClass) clone.className += ' ' + opts.cloneClass;
                copyLayout(source, clone);
                var r = source.getBoundingClientRect();
                clone.style.position = 'fixed';
                clone.style.top = '0';
                clone.style.left = '0';
                clone.style.margin = '0';
                clone.style.zIndex = '9999';
                clone.style.pointerEvents = 'none';
                clone.style.opacity = '0.9';
                clone.style.boxShadow = '0 4px 16px rgba(0,0,0,0.2)';
                clone.style.willChange = 'transform';
                clone.style.transform = 'translate3d(' + r.left + 'px,' + r.top + 'px,0)';
                return clone;
            }

            // ---------- 拖拽 ----------
            function startDrag(e) {
                var item = dragState.draggedItem;
                if (!item) return;
                dragState.thresholdMet = true;

                var rect = item.getBoundingClientRect();
                dragState.offsetX = e.clientX - rect.left;
                dragState.offsetY = e.clientY - rect.top;

                var ph = createPlaceholder(item);
                item.parentNode.insertBefore(ph, item);
                dragState.placeholderElement = ph;
                item.style.display = 'none';

                var clone = createDragClone(item);
                clone.style.transform = 'translate3d(' +
                    (e.clientX - dragState.offsetX) + 'px,' +
                    (e.clientY - dragState.offsetY) + 'px,0)';
                document.body.appendChild(clone);
                dragState.cloneElement = clone;

                document.body.style.cursor = opts.cursor;
                disableSelect();

                triggerCustom($container, 'sortstart',
                    { type: 'sortstart', target: container },
                    { item: $(item), helper: $(clone), placeholder: $(ph) });

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            }

            function onMouseDown(e) {
                if (e.button !== 0) return;
                var target = e.target;
                if (isCancelElement(target)) return;

                var item = target;
                while (item && item.parentNode !== container) item = item.parentNode;
                if (!item || !isSortableItem(item)) return;

                dragState.mouseDownX = e.clientX;
                dragState.mouseDownY = e.clientY;
                dragState.draggedItem = item;

                document.addEventListener('mousemove', onMouseMoveThreshold);
                document.addEventListener('mouseup', onMouseUpThreshold);
            }

            function onMouseMoveThreshold(e) {
                var dx = Math.abs(e.clientX - dragState.mouseDownX);
                var dy = Math.abs(e.clientY - dragState.mouseDownY);
                if (dx > opts.threshold || dy > opts.threshold) {
                    document.removeEventListener('mousemove', onMouseMoveThreshold);
                    document.removeEventListener('mouseup', onMouseUpThreshold);
                    startDrag(e);
                }
            }

            function onMouseUpThreshold() {
                document.removeEventListener('mousemove', onMouseMoveThreshold);
                document.removeEventListener('mouseup', onMouseUpThreshold);
            }

            function detectHorizontal() {
                if (opts.axis === 'horizontal') return true;
                if (opts.axis === 'vertical') return false;
                var cs = getComputedStyle(container);
                if (cs.display === 'flex') {
                    var dir = cs.flexDirection || 'row';
                    return dir.indexOf('row') === 0;
                }
                return false;
            }

            // 核心：移动占位符
            function movePlaceholder(e) {
				var items = getSortableItems();
				if (!items.length) return false;

				var isH = detectHorizontal();
				var closest = null, closestRect = null, minDist = Infinity;

				// 优先：鼠标在哪个 item 范围内
				for (var i = 0; i < items.length; i++) {
					var it = items[i];
					var r = it.getBoundingClientRect();
					if (e.clientX >= r.left && e.clientX <= r.right &&
						e.clientY >= r.top  && e.clientY <= r.bottom) {
						closest = it;
						closestRect = r;
						break;
					}
				}
				// 兜底：中心距离最近
				if (!closest) {
					for (var j = 0; j < items.length; j++) {
						var it2 = items[j];
						var r2 = it2.getBoundingClientRect();
						var cx = r2.left + r2.width / 2;
						var cy = r2.top + r2.height / 2;
						var d = (e.clientX - cx) * (e.clientX - cx) + (e.clientY - cy) * (e.clientY - cy);
						if (d < minDist) { minDist = d; closest = it2; closestRect = r2; }
					}
				}
				if (!closest) return false;

				var ph = dragState.placeholderElement;
				if (!ph) return false;

				// 根据拖动方向动态调整切换比例
				var ratio;
				if (isH) {
					var draggingRight = e.clientX > dragState.mouseDownX;
					ratio = draggingRight ? 0.3 : 0.7;
					var insertBeforeH = e.clientX < closestRect.left + closestRect.width * ratio;
					var refNodeH = insertBeforeH ? closest : closest.nextSibling;
					return doMove(ph, refNodeH);
				} else {
					var draggingDown = e.clientY > dragState.mouseDownY;
					ratio = draggingDown ? 0.3 : 0.7;
					var insertBeforeV = e.clientY < closestRect.top + closestRect.height * ratio;
					var refNodeV = insertBeforeV ? closest : closest.nextSibling;
					return doMove(ph, refNodeV);
				}
			}

			function doMove(ph, refNode) {
				if (refNode === ph) return false;
				var prevBefore = ph.previousSibling;
				var nextBefore = ph.nextSibling;
				var oldRects = recordRects();
				container.insertBefore(ph, refNode);
				if (ph.previousSibling === prevBefore && ph.nextSibling === nextBefore) {
					return false;
				}
				playFlip(oldRects);
				return true;
			}

            function onMouseMove(e) {
                if (!dragState.thresholdMet || !dragState.cloneElement) return;

                dragState.lastEvent = e;
                if (dragState.rafId) return;
                dragState.rafId = raf(function () {
                    dragState.rafId = 0;
                    var ev = dragState.lastEvent;
                    if (!ev || !dragState.cloneElement) return;

                    var x = ev.clientX - dragState.offsetX;
                    var y = ev.clientY - dragState.offsetY;
                    dragState.cloneElement.style.transform =
                        'translate3d(' + x + 'px,' + y + 'px,0)';

                    var moved = movePlaceholder(ev);
                    if (moved) {
                        triggerCustom($container, 'sort',
                            { type: 'sort', target: container },
                            { item: $(dragState.draggedItem), placeholder: $(dragState.placeholderElement) });
                    }
                });
            }

            function onMouseUp() {
                if (dragState.rafId) { caf(dragState.rafId); dragState.rafId = 0; }

                if (!dragState.thresholdMet) {
                    clearGlobalListeners();
                    return;
                }

                var item = dragState.draggedItem;
                var ph = dragState.placeholderElement;

                // 记录占位符当前的位置，用于被拖元素落点动画
                var phRect = ph ? ph.getBoundingClientRect() : null;

                // 把被拖元素插到占位符位置
                if (item && ph) container.insertBefore(item, ph);
                if (item) item.style.display = '';

                // 移除占位符和克隆体
                if (ph && ph.parentNode) ph.parentNode.removeChild(ph);
                if (dragState.cloneElement && dragState.cloneElement.parentNode) {
                    dragState.cloneElement.parentNode.removeChild(dragState.cloneElement);
                }

                // 被拖元素从“克隆体最后位置”飞回“落点”
                if (item && phRect && opts.animation) {
                    var newRect = item.getBoundingClientRect();
                    var dx = phRect.left - newRect.left;
                    var dy = phRect.top - newRect.top;
                    if (dx || dy) {
                        item.style.transition = 'none';
                        item.style.transform = 'translate3d(' + dx + 'px,' + dy + 'px,0)';
                        void item.offsetWidth;
                        item.style.transition = 'transform ' + opts.animation + 'ms ' + opts.easing;
                        item.style.transform = '';
                        setTimeout(function () {
                            item.style.transition = '';
                        }, opts.animation);
                    }
                }

                document.body.style.cursor = '';
                enableSelect();

                triggerCustom($container, 'sortstop',
                    { type: 'sortstop', target: container },
                    { item: $(item) });

                clearGlobalListeners();
                resetState();
            }

            function clearGlobalListeners() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                document.removeEventListener('mousemove', onMouseMoveThreshold);
                document.removeEventListener('mouseup', onMouseUpThreshold);
            }

            function resetState() {
                dragState.thresholdMet = false;
                dragState.draggedItem = null;
                dragState.cloneElement = null;
                dragState.placeholderElement = null;
                dragState.lastEvent = null;
            }

            container.addEventListener('mousedown', onMouseDown);

            // ---------- 实例方法 ----------
            container._sortableCleanup = function () {
                container.removeEventListener('mousedown', onMouseDown);
                clearGlobalListeners();
                if (dragState.cloneElement && dragState.cloneElement.parentNode)
                    dragState.cloneElement.parentNode.removeChild(dragState.cloneElement);
                if (dragState.placeholderElement && dragState.placeholderElement.parentNode)
                    dragState.placeholderElement.parentNode.removeChild(dragState.placeholderElement);
                if (dragState.draggedItem) dragState.draggedItem.style.display = '';
                enableSelect();
                document.body.style.cursor = '';
                delete container._sortableInitialized;
            };

            container._sortableRefresh = function () {};
            container._sortableOption = function (name, value) {
                if (value === undefined) return opts[name];
                opts[name] = value;
                if (name === 'items') itemSelector = normalizeItems(opts.items);
                return value;
            };
        });
    };

    $.fn.sortableDestroy = function () {
        return this.each(function () { if (this._sortableCleanup) this._sortableCleanup(); });
    };
    $.fn.sortableRefresh = function () {
        return this.each(function () { if (this._sortableRefresh) this._sortableRefresh(); });
    };
    $.fn.sortableOption = function (name, value) {
        var r;
        this.each(function () {
            if (this._sortableOption) {
                var v = this._sortableOption(name, value);
                if (r === undefined) r = v;
            }
        });
        return r;
    };
})();