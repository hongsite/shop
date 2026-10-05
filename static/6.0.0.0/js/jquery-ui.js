/**
 * hst-ui.js - 轻量级拖拽排序（修复：任意位置插入）
 * 支持 items 过滤、占位符、克隆体、sortstop 事件（传递 ui.target）
 */
(function($) {
    'use strict';

    function getMatchesMethod(el) {
        return el.matches || el.webkitMatchesSelector || el.msMatchesSelector;
    }

    if (!$.fn.bind) {
        $.fn.bind = function(eventName, handler) {
            if (!this._customEvents) this._customEvents = {};
            if (!this._customEvents[eventName]) this._customEvents[eventName] = [];
            this._customEvents[eventName].push(handler);
            return this;
        };
        $.fn._trigger = function(eventName, extraData) {
            if (!this._customEvents || !this._customEvents[eventName]) return this;
            var handlers = this._customEvents[eventName].slice();
            for (var i = 0; i < handlers.length; i++) {
                try {
                    handlers[i].call(this, extraData);
                } catch(e) {}
            }
            return this;
        };
    }

    $.fn.sortable = function(options) {
        var defaults = {
            items: "> *",
            placeholder: "placeholder",
            cursor: "move",
            cancel: "input,select,option,textarea",
            threshold: 3,
            connectWith: false
        };
        var opts = {};
        for (var key in defaults) {
            opts[key] = (options && options[key] !== undefined) ? options[key] : defaults[key];
        }
        var self = this;

        this.each(function(containerIdx, container) {
            if (container._sortableInitialized) return;
            container._sortableInitialized = true;

            function getSortableItems() {
                var items = [];
                var selector = opts.items || "> *";
                var matches = container.querySelectorAll(selector);
                for (var i = 0; i < matches.length; i++) {
                    var child = matches[i];
                    if (child.parentNode === container && !child._isPlaceholder) {
                        items.push(child);
                    }
                }
                return items;
            }

            function isSortableItem(el) {
                if (!el || el === container) return false;
                var selector = opts.items || "> *";
                var matchesFn = getMatchesMethod(el);
                if (el.parentNode !== container) return false;
                if (matchesFn && matchesFn.call(el, selector)) return true;
                return false;
            }

            function isCancelElement(el) {
                if (!opts.cancel) return false;
                var matches = getMatchesMethod(el);
                if (matches && matches.call(el, opts.cancel)) return true;
                var parent = el.parentNode;
                while (parent && parent !== container) {
                    var matchesParent = getMatchesMethod(parent);
                    if (matchesParent && matchesParent.call(parent, opts.cancel)) return true;
                    parent = parent.parentNode;
                }
                return false;
            }

            var dragState = {
                isDragging: false,
                startX: 0, startY: 0,
                draggedItem: null,
                cloneElement: null,
                placeholderElement: null,
                startIndex: -1,
                thresholdMet: false,
                mouseDownX: 0, mouseDownY: 0,
                targetElement: null
            };

            function disableSelect() {
                document.body.style.userSelect = 'none';
                document.body.style.webkitUserSelect = 'none';
                document.body.style.MozUserSelect = 'none';
            }
            function enableSelect() {
                document.body.style.userSelect = '';
                document.body.style.webkitUserSelect = '';
                document.body.style.MozUserSelect = '';
            }

            function createPlaceholder(item) {
                var ph = document.createElement(item.tagName);
                ph._isPlaceholder = true;
                ph.className = opts.placeholder;
                var rect = item.getBoundingClientRect();
                ph.style.width = rect.width + 'px';
                ph.style.height = rect.height + 'px';
                ph.style.display = getComputedStyle(item).display;
                if (item.tagName === 'TR' && item.children.length === 0) {
                    var td = document.createElement('td');
                    td.innerHTML = '&nbsp;';
                    ph.appendChild(td);
                } else {
                    for (var i = 0; i < item.children.length; i++) {
                        var child = document.createElement(item.children[i].tagName);
                        child.innerHTML = '&nbsp;';
                        ph.appendChild(child);
                    }
                }
                return ph;
            }

            function cloneWithCells(source) {
                if (source.tagName === 'TR' && source.cells.length === 0) {
                    var clone = document.createElement('TR');
                    var td = document.createElement('td');
                    td.textContent = source.textContent || ' ';
                    clone.appendChild(td);
                    for (var i = 0; i < source.attributes.length; i++) {
                        var attr = source.attributes[i];
                        clone.setAttribute(attr.name, attr.value);
                    }
                    return clone;
                } else {
                    return source.cloneNode(true);
                }
            }

            function copyLayout(source, target) {
                var cs = window.getComputedStyle(source);
                var props = [
                    'display', 'boxSizing', 'width', 'height',
                    'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
                    'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
                    'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
                    'fontSize', 'fontFamily', 'fontWeight', 'lineHeight',
                    'textAlign', 'verticalAlign'
                ];
                props.forEach(function(p) {
                    var v = cs.getPropertyValue(p);
                    if (v) target.style.setProperty(p, v);
                });
                var rect = source.getBoundingClientRect();
                target.style.width = rect.width + 'px';
                target.style.height = rect.height + 'px';
            }

            function makeWhiteBlack(el) {
                if (!el) return;
                el.style.backgroundColor = '#ffffff';
                el.style.color = '#000000';
                el.style.opacity = '1';
                el.style.webkitTextFillColor = '#000000';
                el.style.textShadow = '0 0 1px rgba(0,0,0,0.3)';
                for (var i = 0; i < el.children.length; i++) {
                    makeWhiteBlack(el.children[i]);
                }
            }

            function startDrag(e) {
                var target = e.target;
                if (isCancelElement(target)) return;

                var dragItem = target;
                while (dragItem && dragItem.parentNode !== container) {
                    dragItem = dragItem.parentNode;
                    if (!dragItem || dragItem === container) return;
                }
                if (!dragItem || !isSortableItem(dragItem)) return;

                dragState.thresholdMet = true;
                dragState.draggedItem = dragItem;
                dragState.startX = e.clientX;
                dragState.startY = e.clientY;
                dragState.startIndex = getSortableItems().indexOf(dragItem);

                document.body.style.cursor = opts.cursor;
                dragItem.style.cursor = opts.cursor;

                var rect = dragItem.getBoundingClientRect();

                var placeholder = createPlaceholder(dragItem);
                dragItem.parentNode.insertBefore(placeholder, dragItem);
                dragState.placeholderElement = placeholder;
                dragItem.style.display = 'none';

                var clone = cloneWithCells(dragItem);
                if (clone.id) clone.removeAttribute('id');
                copyLayout(dragItem, clone);
                makeWhiteBlack(clone);
                clone.style.position = 'fixed';
                clone.style.top = rect.top + 'px';
                clone.style.left = rect.left + 'px';
                clone.style.zIndex = '9999';
                clone.style.pointerEvents = 'none';
                clone.style.backgroundColor = '#ffffff';
                clone.style.color = '#000000';
                clone.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
                clone.style.opacity = '0.85';
                document.body.appendChild(clone);
                dragState.cloneElement = clone;

                disableSelect();

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            }

            function onMouseDown(e) {
                if (e.button !== 0) return;
                var target = e.target;
                if (isCancelElement(target)) return;

                var dragItem = target;
                while (dragItem && dragItem.parentNode !== container) {
                    dragItem = dragItem.parentNode;
                    if (!dragItem || dragItem === container) return;
                }
                if (!dragItem || !isSortableItem(dragItem)) return;

                e.preventDefault();
                e.stopPropagation();

                dragState.isDragging = false;
                dragState.thresholdMet = false;
                dragState.mouseDownX = e.clientX;
                dragState.mouseDownY = e.clientY;
                dragState.draggedItem = null;

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

            function onMouseUpThreshold(e) {
                document.removeEventListener('mousemove', onMouseMoveThreshold);
                document.removeEventListener('mouseup', onMouseUpThreshold);
            }

            // ================= 核心修复：基于最近元素的插入 =================
            function onMouseMove(e) {
                if (!dragState.thresholdMet) return;
                if (dragState.cloneElement) {
                    var cloneRect = dragState.cloneElement.getBoundingClientRect();
                    var newLeft = e.clientX - (dragState.startX - cloneRect.left);
                    var newTop = e.clientY - (dragState.startY - cloneRect.top);
                    dragState.cloneElement.style.left = newLeft + 'px';
                    dragState.cloneElement.style.top = newTop + 'px';
                }

                // 获取所有非占位符子元素
                var children = container.children;
                var items = [];
                for (var i = 0; i < children.length; i++) {
                    if (!children[i]._isPlaceholder) {
                        items.push(children[i]);
                    }
                }
                if (items.length === 0) return;

                // 找出距离鼠标最近的可排序元素
                var closest = null;
                var closestDist = Infinity;
                for (var i = 0; i < items.length; i++) {
                    var rect = items[i].getBoundingClientRect();
                    var cx = rect.left + rect.width / 2;
                    var cy = rect.top + rect.height / 2;
                    var dx = e.clientX - cx;
                    var dy = e.clientY - cy;
                    var dist = dx * dx + dy * dy;
                    if (dist < closestDist) {
                        closestDist = dist;
                        closest = items[i];
                    }
                }
                if (!closest) return;

                // 判断鼠标在 closest 的哪一侧（水平方向为主）
                var rect = closest.getBoundingClientRect();
                var cx = rect.left + rect.width / 2;
                var insertBefore = (e.clientX < cx);

                // 移动占位符到目标位置
                if (insertBefore) {
                    container.insertBefore(dragState.placeholderElement, closest);
                } else {
                    var nextSibling = closest.nextSibling;
                    // 如果下一个兄弟是占位符或不存在，则追加到末尾
                    if (nextSibling && !nextSibling._isPlaceholder) {
                        container.insertBefore(dragState.placeholderElement, nextSibling);
                    } else {
                        container.appendChild(dragState.placeholderElement);
                    }
                }

                // 记录目标元素（用于事件传递）
                dragState.targetElement = closest;
            }
            // ============================================================

            function onMouseUp(e) {
                if (!dragState.thresholdMet) {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    return;
                }
                dragState.thresholdMet = false;

                if (dragState.cloneElement && dragState.cloneElement.parentNode) {
                    dragState.cloneElement.parentNode.removeChild(dragState.cloneElement);
                }
                dragState.cloneElement = null;

                if (dragState.draggedItem) {
                    dragState.draggedItem.style.display = '';
                }

                if (dragState.draggedItem && dragState.placeholderElement && dragState.placeholderElement.parentNode === container) {
                    container.insertBefore(dragState.draggedItem, dragState.placeholderElement);
                }

                if (dragState.placeholderElement && dragState.placeholderElement.parentNode) {
                    dragState.placeholderElement.parentNode.removeChild(dragState.placeholderElement);
                }

                document.body.style.cursor = '';
                if (dragState.draggedItem) dragState.draggedItem.style.cursor = '';
                enableSelect();

                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                var sortstopEvent = {
                    type: 'sortstop',
                    ui: {
                        item: dragState.draggedItem,
                        target: dragState.targetElement
                    },
                    stopPropagation: function() {},
                    preventDefault: function() {}
                };
                if (self._trigger) {
                    self._trigger('sortstop', sortstopEvent);
                } else {
                    if (self._customEvents && self._customEvents.sortstop) {
                        var handlers = self._customEvents.sortstop.slice();
                        for (var i = 0; i < handlers.length; i++) {
                            handlers[i].call(self, sortstopEvent);
                        }
                    }
                }

                dragState.draggedItem = null;
                dragState.placeholderElement = null;
                dragState.targetElement = null;
            }

            container.addEventListener('mousedown', onMouseDown);

            container._sortableCleanup = function() {
                container.removeEventListener('mousedown', onMouseDown);
                document.removeEventListener('mousemove', onMouseMoveThreshold);
                document.removeEventListener('mouseup', onMouseUpThreshold);
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                delete container._sortableInitialized;
            };
        });

        return this;
    };
})(window.$ || window.jQuery);