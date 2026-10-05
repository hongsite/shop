/* ============================================================
 * donut-chart.js —— 通用环形占比图（纯 CSS + 原生 JS，无依赖）
 *
 * 用法：
 *   <script src="donut-chart.js"></script>
 *   const chart = createDonutChart('#container', [
 *       { label: '健康 (A级)',   value: 42, color: '#10b981' },
 *       { label: '良好 (B级)',   value: 26, color: '#3b82f6' },
 *       { label: '一般 (C级)',   value: 18, color: '#f59e0b' },
 *       { label: '待回收 (D级)', value: 14, color: '#ef4444' },
 *   ], {
 *       title: '设备状态分布',
 *       size: 220,
 *       thickness: 46,
 *       centerLabel: '设备总数',
 *       tooltip: true,
 *       animate: true,
 *   });
 *
 * 支持后续更新：
 *   chart.update(newData, newOptions);
 *   chart.destroy();
 * ============================================================ */
(function (global) {
  'use strict';

  /* ---------- 默认配置 ---------- */
  const DEFAULTS = {
    size: 180,            // 图表直径（px），可按需调整
    thickness: 40,        // 环的粗细（px）
    gap: 2,               // 扇区之间的间隔（角度）
    title: '',            // 卡片标题（空则不显示）
    showCenter: true,     // 是否显示中心文字
    centerLabel: '总数',  // 中心上方小字
    centerValue: null,    // 中心数值，默认 = 数据总和
    showLegend: true,     // 是否显示图例
    tooltip: true,        // 是否显示悬停提示框
    hoverEffect: true,    // 悬停时扇区放大 + 其他扇区变暗
    animate: false,       // 是否带入场动画
    showLabels: true,     // 扇区上是否显示百分比
    labelColor: '#fff',   // 扇区文字颜色
    minLabelPct: 6,       // 百分比小于该值的扇区不显示标签（避免过小扇区文字重叠）
  };

  /* ---------- 工具函数 ---------- */
  function polar(cx, cy, r, angle) {
    const rad = (angle - 90) * Math.PI / 180;   // 从 12 点方向开始
    return [cx + r * Math.cos(rad), cy + r * Math.sin(rad)];
  }

  function ringSector(cx, cy, rOuter, rInner, start, end) {
    const large = (end - start) > 180 ? 1 : 0;
    const [x1, y1] = polar(cx, cy, rOuter, start);
    const [x2, y2] = polar(cx, cy, rOuter, end);
    const [x3, y3] = polar(cx, cy, rInner, end);
    const [x4, y4] = polar(cx, cy, rInner, start);
    return `M ${x1} ${y1} A ${rOuter} ${rOuter} 0 ${large} 1 ${x2} ${y2} ` +
           `L ${x3} ${y3} A ${rInner} ${rInner} 0 ${large} 0 ${x4} ${y4} Z`;
  }

  function round1(n) {
    return (Math.round(n * 10) / 10).toString().replace(/\.0$/, '');
  }

  /* ---------- 运行时自动注入样式（仅注入一次） ---------- */
  function injectStyle() {
    if (document.getElementById('dc-style')) return;
    const css = `
      
    `;
    const style = document.createElement('style');
    style.id = 'dc-style';
    style.textContent = css;
    document.head.appendChild(style);
  }

  /* ---------- 主函数 ---------- */
  function createDonutChart(container, data, options) {
    const el = typeof container === 'string' ? document.querySelector(container) : container;
    if (!el) throw new Error('createDonutChart: 找不到容器 ' + container);

    const opts = Object.assign({}, DEFAULTS, options || {});
    injectStyle();

    let current = -1;          // 当前 hover 的扇区下标
    let tooltipEl = null;

    /* ---- 数据清洗：过滤无效项 ---- */
    const clean = (data || [])
  .map(d => d && Object.assign({}, d, { value: Number(d.value) }))
  .filter(d => d && isFinite(d.value) && d.value > 0);

    function build() {
      el.innerHTML = '';
      if (!clean.length) return;

      const total = clean.reduce((s, d) => s + d.value, 0);
      const size = opts.size;
      const cx = size / 2, cy = size / 2;
      const rOuter = size / 2 - 2;
      const rInner = rOuter - opts.thickness;
      let legend = null;
      if (rInner < 4) throw new Error('createDonutChart: thickness 过大，环内径不足');

      /* 卡片容器 */
      const card = document.createElement('div');
      card.className = 'dc-card';      

      /* 环形图主体 */
      const wrap = document.createElement('div');
      wrap.className = 'dc-wrap';
      wrap.style.width = size + 'px';
      wrap.style.height = size + 'px';

      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('class', 'dc-ring' + (opts.animate ? ' animate' : ''));
      svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
      svg.setAttribute('role', 'img');
      svg.setAttribute('aria-label', opts.title || '环形占比图');

      /* 扇区 */
      let cursor = 0;
      clean.forEach((d, i) => {
        const sweep = d.value / total * 360;
        const gap = sweep > opts.gap * 2 ? opts.gap : 0;
        const start = cursor + gap / 2;
        const end = cursor + sweep - gap / 2;

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', ringSector(cx, cy, rOuter, rInner, start, end));
        path.setAttribute('fill', d.color);
        path.setAttribute('class', 'dc-slice');
        if (opts.animate) path.style.animationDelay = (i * 0.08) + 's';
        path.dataset.index = i;
        svg.appendChild(path);

        /* 扇区百分比标签（显示在扇区中间） */
        const pct = d.value / total * 100;
        if (opts.showLabels && pct >= opts.minLabelPct) {
          const mid = (start + end) / 2;
          const [lx, ly] = polar(cx, cy, (rOuter + rInner) / 2, mid);
          const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          label.setAttribute('x', lx);
          label.setAttribute('y', ly);
          label.setAttribute('text-anchor', 'middle');
          label.setAttribute('dominant-baseline', 'central');
          label.setAttribute('fill', opts.labelColor);
          label.setAttribute('font-size', Math.max(9, Math.round(size * 0.055)));
          label.setAttribute('font-weight', '600');
          label.setAttribute('stroke', 'rgba(0,0,0,.28)');
          label.setAttribute('stroke-width', Math.max(0.6, size * 0.004));
          label.setAttribute('paint-order', 'stroke');
          label.setAttribute('class', 'dc-label');
          label.textContent = round1(pct);
          svg.appendChild(label);
        }

        cursor += sweep;
      });
      wrap.appendChild(svg);

      /* 中心文字（数字精确居中，标签浮于数字上方） */
      if (opts.showCenter) {
        const center = document.createElement('div');
        center.className = 'dc-center';
        center.innerHTML =
          `<span class="dc-clabel"></span><span class="dc-cvalue"></span>`;
        const clabel = center.querySelector('.dc-clabel');
        clabel.textContent = opts.centerLabel;
        if (!opts.centerLabel) clabel.style.display = 'none';
        center.querySelector('.dc-cvalue').textContent =
          opts.centerValue != null ? opts.centerValue : total;
        wrap.appendChild(center);
      }
      card.appendChild(wrap);

      /* 图例 */
      if (opts.showLegend) {
        legend = document.createElement('div');
        legend.className = 'dc-legend';
        clean.forEach((d, i) => {
          const pct = round1(d.value / total * 100);
          const item = document.createElement('div');
          item.className = 'dc-legend-item';
          item.innerHTML =
            `<span class="dc-swatch" style="background:${d.color}"></span>` +
            `<span></span><span class="dc-pct"></span>`;
          item.querySelectorAll('span')[1].textContent = d.label;
          //item.querySelector('.dc-pct').textContent = pct + '%';
          item.dataset.index = i;
          legend.appendChild(item);
        });
        card.appendChild(legend);
      }
      el.appendChild(card);

      /* ---- 交互绑定 ---- */
      const slices = svg.querySelectorAll('.dc-slice');
      const legendItems = legend ? legend.querySelectorAll('.dc-legend-item') : [];

      function showTooltip(i, x, y) {
        if (!opts.tooltip) return;
        if (!tooltipEl) {
          tooltipEl = document.createElement('div');
          tooltipEl.className = 'dc-tooltip';
          document.body.appendChild(tooltipEl);
        }
        const d = clean[i];
        const pct = round1(d.value / total * 100);
        tooltipEl.innerHTML =
          `<span class="dc-dot" style="background:${d.color}"></span>${d.label}: ${pct}%`;
        tooltipEl.classList.add('dc-show');
        const tx = Math.min(x, window.innerWidth - tooltipEl.offsetWidth - 8);
        const ty = Math.max(y, tooltipEl.offsetHeight + 8);
        tooltipEl.style.left = tx + 'px';
        tooltipEl.style.top = ty + 'px';
      }

      function hideTooltip() {
        if (tooltipEl) tooltipEl.classList.remove('dc-show');
      }

      function setActive(i) {
        if (i === current) return;
        current = i;
        slices.forEach(s => s.classList.toggle('dc-hovered', +s.dataset.index === i));
        if (opts.hoverEffect) svg.classList.toggle('dim', i >= 0);
        legendItems.forEach(li =>
          li.style.opacity = (i < 0 || +li.dataset.index === i) ? '1' : '.35');
      }

      slices.forEach(s => {
        s.addEventListener('mousemove', e => {
          showTooltip(+s.dataset.index, e.clientX, e.clientY);
          setActive(+s.dataset.index);
        });
        s.addEventListener('mouseleave', () => { hideTooltip(); setActive(-1); });
      });

      legendItems.forEach(li => {
        li.addEventListener('mouseenter', () => {
          const r = wrap.getBoundingClientRect();
          showTooltip(+li.dataset.index, r.left + r.width / 2, r.top + r.height / 2);
          setActive(+li.dataset.index);
        });
        li.addEventListener('mouseleave', () => { hideTooltip(); setActive(-1); });
      });

      return { total };
    }

    const info = build();

    /* ---- 对外暴露的实例 API ---- */
    return {
      el,
      data: clean,
      options: opts,
      total: info.total,
      update(newData, newOptions) {
        if (newOptions) Object.assign(opts, newOptions);
        return createDonutChart(el, newData || clean, opts);
      },
      destroy() {
        if (tooltipEl && tooltipEl.parentNode) tooltipEl.parentNode.removeChild(tooltipEl);
        el.innerHTML = '';
        tooltipEl = null;
      },
    };
  }

  global.createDonutChart = createDonutChart;
})(typeof window !== 'undefined' ? window : globalThis);
