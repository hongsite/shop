/**
 * 柱状图组件 - 适配 Kodo 库 (hst.js)
 * 依赖：全局 $ (Kodo) 已加载
 * 使用方式：new nchars({ did: '容器id', data: [...], str: [...] })
 */
(function($) {
    'use strict';

    // 整数格式化
    function formatInteger(val) {
        if (val === undefined || isNaN(val)) return '0';
        return Math.round(val).toString();
    }

    // 千位格式化（Y轴用）
    function formatNumber(val, digits) {
        if (isNaN(val)) return '0';
        return val.toFixed(digits);
    }

    function nchars(option) {
        this.did = option.did;
        this.data = option.data || [];
        this.str = option.str || [];
        this.deng = (option.deng !== undefined) ? option.deng : 5;
        this.title = option.title || '';
        this.style = option.style || {};
        this.barWidth = option.barWidth || 12;
        this.barMargin = option.barMargin || 2;
        this.init();
    }

    nchars.prototype = {
        init: function() {
            var $container = $('#' + this.did);
            if (!$container.length) {
                console.error('容器不存在: ' + this.did);
                return;
            }
            var container = $container.elements[0];

            if (!this.data.length || !this.data[0].length) {
                container.innerHTML = '<div style="padding:20px;text-align:center;">暂无数据</div>';
                return;
            }

            // 获取容器实际高度（含 padding/border，使用 clientHeight）
            var dh = container.clientHeight;
            // 计算标题占位高度
            var titleH = 0;
            if (this.title) {
                var tmpH = document.createElement('h5');
                tmpH.style.cssText = 'visibility:hidden;position:absolute;';
                tmpH.textContent = this.title;
                container.appendChild(tmpH);
                titleH = tmpH.offsetHeight;
                container.removeChild(tmpH);
                if (titleH === 0) titleH = 40;
            }
            var graphHeight = dh - titleH - 35;
            if (graphHeight < 50) graphHeight = 50;

            // 构建DOM结构
            var html = '';
            if (this.title) html += '<h5>' + this.title + '</h5>';
            html += '<div class="graph"><ul class="x-axis"></ul><div class="bars"></div><ul class="y-axis"></ul></div>';
            $container.html(html);

            var $graph = $container.find('.graph');
            var $bars = $container.find('.bars');
            var $yAxis = $container.find('.y-axis');

            $graph.css('height', graphHeight + 'px');
            $bars.css('height', graphHeight + 'px');
            $yAxis.css('height', graphHeight + 'px');

            // X轴标签
            var seriesLen = this.data[0].length;
            var xLabels = (this.str.length === seriesLen) ? this.str : this.str.slice(0, seriesLen);
            while (xLabels.length < seriesLen) xLabels.push('');
            var xHtml = '';
            for (var i = 0; i < seriesLen; i++) {
                xHtml += '<li><span>' + (xLabels[i] || ('第' + (i+1) + '项')) + '</span></li>';
            }
            $container.find('.x-axis').html(xHtml);

            // 计算最大值
            var maxVal = 0;
            var groupCount = this.data.length;
            for (var g = 0; g < groupCount; g++) {
                var groupData = this.data[g];
                for (var idx = 0; idx < groupData.length; idx++) {
                    var val = parseFloat(groupData[idx]);
                    if (!isNaN(val) && val > maxVal) maxVal = val;
                }
            }
            if (maxVal <= 0) maxVal = 1;

            // Y轴刻度
            var step = Math.ceil(maxVal / this.deng);
            var yHtml = '';
            for (var i = this.deng; i >= 0; i--) {
                var val = i * step;
                var displayVal = (val >= 1000) ? formatNumber(val / 1000, 1) + 'k' : formatNumber(val, 0);
                yHtml += '<li><span>' + displayVal + '</span></li>';
            }
            $yAxis.html(yHtml);
            var liHeight = graphHeight / this.deng;
            $yAxis.find('li').css('height', liHeight + 'px');

            // 生成柱子（使用原生DOM创建，避免Kodo append的克隆行为）
            var barsDiv = $bars.elements[0];
            barsDiv.innerHTML = '';  // 清空
            for (var idx = 0; idx < seriesLen; idx++) {
                var barGroup = document.createElement('div');
                barGroup.className = 'bar-group';
                for (var g = 0; g < groupCount; g++) {
                    var value = parseFloat(this.data[g][idx]);
                    if (isNaN(value)) value = 0;
                    var percent = (value / maxVal) * 100;
                    var bar = document.createElement('span');
                    bar.className = 'bar stat-' + (g+1);
                    bar.style.width = this.barWidth + 'px';
                    bar.style.marginLeft = this.barMargin + 'px';
                    bar.style.marginRight = this.barMargin + 'px';
                    bar.style.height = percent + '%';
                    // 数值标签
                    var spanVal = document.createElement('span');
                    spanVal.textContent = formatInteger(value);
                    if (percent === 0) {
                        spanVal.style.bottom = 'auto';
                        spanVal.style.top = '-18px';
                    }
                    bar.appendChild(spanVal);
                    barGroup.appendChild(bar);
                }
                barsDiv.appendChild(barGroup);
            }

            // 自适应窗口大小变化
            var self = this;
            var resizeHandler = function() {
                self.init();
            };
            window.removeEventListener('resize', this._resizeListener);
            this._resizeListener = resizeHandler;
            window.addEventListener('resize', this._resizeListener);
        },

        resize: function() {
            this.init();
        },

        destroy: function() {
            if (this._resizeListener) {
                window.removeEventListener('resize', this._resizeListener);
            }
        }
    };

    window.nchars = nchars;
})(window.$);  // 传入全局 $（Kodo 实例）