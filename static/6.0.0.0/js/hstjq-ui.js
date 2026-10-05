/**
 * 适配 hst.js(Kodo 轻量DOM库) sortable拖拽排序
 * 不再依赖jQuery，仅使用Kodo对外标准API
 * 事件：sortstart、sort、sortstop (e, ui) 双参数回调
 */
(function () {
    'use strict';

    function getMatchesMethod(el) {
        return el.matches || el.webkitMatchesSelector || el.msMatchesSelector;
    }

    /**
     * Kodo实例自定义事件缓存（替代jQuery trigger）
     */
    const eventMap = new WeakMap();
    function getEventStore($el) {
        const dom = $el.get(0);
        if (!eventMap.has(dom)) {
            eventMap.set(dom, {});
        }
        return eventMap.get(dom);
    }

    /**
     * 绑定自定义事件（扩展Kodo实例on，支持sortstart/sortstop自定义事件）
     */
    function bindCustomEvent($container, eventName, handler) {
        const store = getEventStore($container);
        if (!store[eventName]) store[eventName] = [];
        store[eventName].push(handler);
    }

    /**
     * 触发自定义事件
     */
    function triggerCustom($container, eventName, eventObj, ui) {
        const store = getEventStore($container);
        if (!store[eventName]) return;
        const handlers = store[eventName].slice();
        handlers.forEach(fn => {
            fn.call($container.get(0), eventObj, ui);
        });
    }

    // 扩展 Kodo.prototype.on 支持自定义事件监听
    const originalOn = $.fn.on;
    $.fn.on = function (eventType, callback) {
        const customEvents = ['sortstart', 'sort', 'sortstop'];
        if (customEvents.includes(eventType)) {
            bindCustomEvent(this, eventType, callback);
            return this;
        }
        return originalOn.call(this, eventType, callback);
    };

    $.fn.sortable = function (options) {
        const defaults = {
            items: "> *",
            placeholder: "placeholder",
            cursor: "move",
            cancel: "input,select,option,textarea",
            threshold: 3,
            connectWith: false
        };
        const opts = {};
        for (const key in defaults) {
            opts[key] = (options && options[key] !== undefined) ? options[key] : defaults[key];
        }

        return this.each(function () {
            const container = this;
            const $container = $(container);
            if (container._sortableInitialized) return;
            container._sortableInitialized = true;

            const dragState = {
                mouseDownX: 0,
                mouseDownY: 0,
                draggedItem: null,
                cloneElement: null,
                placeholderElement: null,
                thresholdMet: false
            };

            function getSortableItems() {
                const list = [];
                const all = container.querySelectorAll(opts.items);
                for (let i = 0; i < all.length; i++) {
                    const node = all[i];
                    if (node.parentNode === container && !node._isPlaceholder) {
                        list.push(node);
                    }
                }
                return list;
            }

            function isSortableItem(el) {
                if (!el || el === container) return false;
                const matchesFn = getMatchesMethod(el);
                return el.parentNode === container && matchesFn.call(el, opts.items);
            }

            function isCancelElement(el) {
                if (!opts.cancel || !el) return false;
                let cur = el;
                while (cur && cur !== container) {
                    const matches = getMatchesMethod(cur);
                    if (matches && matches.call(cur, opts.cancel)) return true;
                    cur = cur.parentNode;
                }
                return false;
            }

            function disableSelect() {
                document.body.style.userSelect = 'none';
                document.body.style.webkitUserSelect = 'none';
            }
            function enableSelect() {
                document.body.style.userSelect = '';
                document.body.style.webkitUserSelect = '';
            }

            function createPlaceholder(item) {
                const ph = document.createElement(item.tagName);
                ph._isPlaceholder = true;
                ph.className = opts.placeholder;
                const rect = item.getBoundingClientRect();
                ph.style.width = `${rect.width}px`;
                ph.style.height = `${rect.height}px`;
                ph.style.display = getComputedStyle(item).display;
                return ph;
            }

            function copyLayout(source, target) {
                const cs = window.getComputedStyle(source);
                const props = [
                    'display', 'boxSizing', 'padding', 'margin',
                    'borderWidth', 'fontSize', 'lineHeight', 'textAlign'
                ];
                props.forEach(p => {
                    const val = cs.getPropertyValue(p);
                    if (val) target.style.setProperty(p, val);
                });
                const rect = source.getBoundingClientRect();
                target.style.width = `${rect.width}px`;
                target.style.height = `${rect.height}px`;
            }

            function createDragClone(source) {
                const clone = source.cloneNode(true);
                if (clone.id) clone.removeAttribute('id');
                copyLayout(source, clone);
                const rect = source.getBoundingClientRect();
                clone.style.position = 'fixed';
                clone.style.top = `${rect.top}px`;
                clone.style.left = `${rect.left}px`;
                clone.style.zIndex = '9999';
                clone.style.pointerEvents = 'none';
                clone.style.opacity = '0.85';
                clone.style.boxShadow = '0 2px 10px rgba(0,0,0,0.25)';
                return clone;
            }

            function startDrag(e) {
                const dragItem = dragState.draggedItem;
                dragState.thresholdMet = true;

                const placeholder = createPlaceholder(dragItem);
                dragItem.parentNode.insertBefore(placeholder, dragItem);
                dragState.placeholderElement = placeholder;
                dragItem.style.display = 'none';

                const clone = createDragClone(dragItem);
                document.body.appendChild(clone);
                dragState.cloneElement = clone;

                document.body.style.cursor = opts.cursor;
                disableSelect();

                const ui = { item: $(dragItem) };
                const event = { type: 'sortstart', target: container };
                triggerCustom($container, 'sortstart', event, ui);

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            }

            function onMouseDown(e) {
                if (e.button !== 0) return;
                const target = e.target;
                if (isCancelElement(target)) return;

                let dragItem = target;
                while (dragItem && dragItem.parentNode !== container) {
                    dragItem = dragItem.parentNode;
                }
                if (!dragItem || !isSortableItem(dragItem)) return;

                dragState.mouseDownX = e.clientX;
                dragState.mouseDownY = e.clientY;
                dragState.draggedItem = dragItem;

                document.addEventListener('mousemove', onMouseMoveThreshold);
                document.addEventListener('mouseup', onMouseUpThreshold);
            }

            function onMouseMoveThreshold(e) {
                const dx = Math.abs(e.clientX - dragState.mouseDownX);
                const dy = Math.abs(e.clientY - dragState.mouseDownY);
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

            function onMouseMove(e) {
                if (!dragState.thresholdMet || !dragState.cloneElement) return;

                dragState.cloneElement.style.left = `${e.clientX}px`;
                dragState.cloneElement.style.top = `${e.clientY}px`;

                const items = getSortableItems();
                if (items.length === 0) return;

                let closest = null;
                let minDist = Infinity;
                let closestRect = null;

                for (const item of items) {
                    const rect = item.getBoundingClientRect();
                    const cx = rect.left + rect.width / 2;
                    const cy = rect.top + rect.height / 2;
                    const dist = Math.hypot(e.clientX - cx, e.clientY - cy);
                    if (dist < minDist) {
                        minDist = dist;
                        closest = item;
                        closestRect = rect;
                    }
                }
                if (!closest || closest === dragState.draggedItem) return;

                const isHorizontal = getComputedStyle(container).display === 'flex';
                let insertBefore;
                if (isHorizontal) {
                    insertBefore = e.clientX < closestRect.left + closestRect.width / 2;
                } else {
                    insertBefore = e.clientY < closestRect.top + closestRect.height / 2;
                }

                if (insertBefore) {
                    container.insertBefore(dragState.placeholderElement, closest);
                } else {
                    container.insertBefore(dragState.placeholderElement, closest.nextSibling);
                }

                const ui = { item: $(dragState.draggedItem) };
                const event = { type: 'sort', target: container };
                triggerCustom($container, 'sort', event, ui);
            }

            function onMouseUp() {
                if (!dragState.thresholdMet) {
                    clearGlobalListeners();
                    return;
                }

                const dragItem = dragState.draggedItem;
                const placeholder = dragState.placeholderElement;

                if (dragItem && placeholder) {
                    container.insertBefore(dragItem, placeholder);
                }
                if (dragItem) dragItem.style.display = '';

                if (dragState.cloneElement && dragState.cloneElement.parentNode) {
                    dragState.cloneElement.remove();
                }
                if (placeholder && placeholder.parentNode) {
                    placeholder.remove();
                }

                document.body.style.cursor = '';
                enableSelect();

                const ui = { item: $(dragItem) };
                const event = { type: 'sortstop', target: container };
                triggerCustom($container, 'sortstop', event, ui);

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
            }

            container.addEventListener('mousedown', onMouseDown);

            // 销毁方法
            container._sortableCleanup = function () {
                container.removeEventListener('mousedown', onMouseDown);
                clearGlobalListeners();
                delete container._sortableInitialized;
            };
        });

        return this;
    };
})();