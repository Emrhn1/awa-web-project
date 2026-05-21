const API_BASE = 'http://localhost:8000/api';
const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w185';
const TMDB_POSTER_BASE = 'https://image.tmdb.org/t/p/w342';
const chartColors = ['#b33f31', '#366c9f', '#2f7d62', '#c99a3f', '#7f2c24'];

const state = {
    nominations: [],
    actors: [],
    categories: [],
    editions: [],
    filtered: [],
};

const els = {
    apiDot: document.getElementById('api-dot'),
    apiStatus: document.getElementById('api-status'),
    yearFilter: document.getElementById('year-filter'),
    categoryFilter: document.getElementById('category-filter'),
    winnerFilter: document.getElementById('winner-filter'),
    searchFilter: document.getElementById('search-filter'),
    resetFilters: document.getElementById('reset-filters'),
    nominationsMetric: document.getElementById('metric-nominations'),
    winnersMetric: document.getElementById('metric-winners'),
    actorsMetric: document.getElementById('metric-actors'),
    filmsMetric: document.getElementById('metric-films'),
    nominationsBody: document.getElementById('nominations-body'),
    yearChart: document.getElementById('year-chart'),
    categoryChart: document.getElementById('category-chart'),
    winnerChart: document.getElementById('winner-chart'),
    actorDetail: document.getElementById('actor-detail'),
    detailState: document.getElementById('detail-state'),
    exportCsv: document.getElementById('export-csv'),
    exportJson: document.getElementById('export-json'),
};

function setApiState(kind, message) {
    els.apiDot.className = `state-dot ${kind}`;
    els.apiStatus.textContent = message;
}

async function apiGet(path) {
    const res = await fetch(`${API_BASE}${path}`);
    if (!res.ok) {
        throw new Error(`${path} returned HTTP ${res.status}`);
    }
    return res.json();
}

function uniqueValues(items, key) {
    return [...new Set(items.map((item) => item[key]))].filter(Boolean);
}

function countBy(items, key) {
    return items.reduce((acc, item) => {
        const value = item[key];
        acc[value] = (acc[value] || 0) + 1;
        return acc;
    }, {});
}

function fillSelect(select, values, formatter = (value) => value) {
    for (const value of values) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = formatter(value);
        select.appendChild(option);
    }
}

function setupFilters() {
    const years = uniqueValues(state.nominations, 'year').sort((a, b) => b - a);
    const categories = uniqueValues(state.nominations, 'category').sort();

    fillSelect(els.yearFilter, years);
    fillSelect(els.categoryFilter, categories);

    for (const input of [els.yearFilter, els.categoryFilter, els.winnerFilter, els.searchFilter]) {
        input.addEventListener('input', renderDashboard);
    }

    els.resetFilters.addEventListener('click', () => {
        els.yearFilter.value = '';
        els.categoryFilter.value = '';
        els.winnerFilter.value = '';
        els.searchFilter.value = '';
        renderDashboard();
    });
}

function applyFilters() {
    const year = els.yearFilter.value;
    const category = els.categoryFilter.value;
    const winner = els.winnerFilter.value;
    const search = els.searchFilter.value.trim().toLowerCase();

    state.filtered = state.nominations.filter((nomination) => {
        const matchesYear = !year || String(nomination.year) === year;
        const matchesCategory = !category || nomination.category === category;
        const matchesWinner = winner === '' || String(nomination.is_winner) === winner;
        const matchesSearch = !search
            || nomination.actor.toLowerCase().includes(search)
            || nomination.film.toLowerCase().includes(search);

        return matchesYear && matchesCategory && matchesWinner && matchesSearch;
    });
}

function renderMetrics() {
    const actorCount = uniqueValues(state.filtered, 'actor_id').length;
    const filmCount = uniqueValues(state.filtered, 'film_id').length;
    const winnerCount = state.filtered.filter((item) => Number(item.is_winner) === 1).length;

    els.nominationsMetric.textContent = state.filtered.length;
    els.winnersMetric.textContent = winnerCount;
    els.actorsMetric.textContent = actorCount;
    els.filmsMetric.textContent = filmCount;
}

function emptySvgMessage(container, message) {
    container.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'empty-state';
    p.textContent = message;
    container.appendChild(p);
}

function svgEl(name, attrs = {}) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', name);
    for (const [key, value] of Object.entries(attrs)) {
        el.setAttribute(key, value);
    }
    return el;
}

function renderYearChart() {
    if (!state.filtered.length) {
        emptySvgMessage(els.yearChart, 'No data matches the selected filters.');
        return;
    }

    const counts = countBy(state.filtered, 'year');
    const years = Object.keys(counts).map(Number).sort((a, b) => a - b);
    const max = Math.max(...Object.values(counts));
    const width = 520;
    const height = 280;
    const margin = { top: 20, right: 18, bottom: 42, left: 38 };
    const plotWidth = width - margin.left - margin.right;
    const plotHeight = height - margin.top - margin.bottom;
    const barWidth = plotWidth / years.length * 0.62;

    const svg = svgEl('svg', { viewBox: `0 0 ${width} ${height}`, role: 'presentation' });
    svg.appendChild(svgEl('line', {
        class: 'axis',
        x1: margin.left,
        y1: margin.top + plotHeight,
        x2: width - margin.right,
        y2: margin.top + plotHeight,
    }));

    years.forEach((year, index) => {
        const value = counts[year];
        const x = margin.left + (plotWidth / years.length) * index + (plotWidth / years.length - barWidth) / 2;
        const h = (value / max) * plotHeight;
        const y = margin.top + plotHeight - h;

        svg.appendChild(svgEl('rect', {
            class: 'bar',
            x,
            y,
            width: barWidth,
            height: h,
            rx: 4,
        }));

        const label = svgEl('text', { x: x + barWidth / 2, y: y - 8, 'text-anchor': 'middle' });
        label.textContent = value;
        svg.appendChild(label);

        const yearLabel = svgEl('text', {
            x: x + barWidth / 2,
            y: height - 14,
            'text-anchor': 'middle',
        });
        yearLabel.textContent = year;
        svg.appendChild(yearLabel);
    });

    els.yearChart.replaceChildren(svg);
}

function renderCategoryChart() {
    if (!state.filtered.length) {
        emptySvgMessage(els.categoryChart, 'No trend data is available for the selected filters.');
        return;
    }

    const width = 560;
    const height = 280;
    const margin = { top: 24, right: 28, bottom: 62, left: 38 };
    const plotWidth = width - margin.left - margin.right;
    const plotHeight = height - margin.top - margin.bottom;
    const years = uniqueValues(state.nominations, 'year').sort((a, b) => a - b);
    const categories = uniqueValues(state.filtered, 'category').sort();

    const matrix = categories.map((category) => years.map((year) => (
        state.filtered.filter((item) => item.year === year && item.category === category).length
    )));
    const max = Math.max(1, ...matrix.flat());
    const svg = svgEl('svg', { viewBox: `0 0 ${width} ${height}`, role: 'presentation' });

    svg.appendChild(svgEl('line', {
        class: 'axis',
        x1: margin.left,
        y1: margin.top + plotHeight,
        x2: width - margin.right,
        y2: margin.top + plotHeight,
    }));

    years.forEach((year, index) => {
        const x = years.length === 1
            ? margin.left + plotWidth / 2
            : margin.left + (plotWidth / (years.length - 1)) * index;
        const label = svgEl('text', { x, y: height - 36, 'text-anchor': 'middle' });
        label.textContent = year;
        svg.appendChild(label);
    });

    categories.forEach((category, categoryIndex) => {
        const points = years.map((year, yearIndex) => {
            const count = state.filtered.filter((item) => item.year === year && item.category === category).length;
            const x = years.length === 1
                ? margin.left + plotWidth / 2
                : margin.left + (plotWidth / (years.length - 1)) * yearIndex;
            const y = margin.top + plotHeight - (count / max) * plotHeight;
            return { x, y, count };
        });
        const color = chartColors[categoryIndex % chartColors.length];
        const path = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');

        svg.appendChild(svgEl('path', { class: 'line', d: path, stroke: color }));

        points.forEach((point) => {
            svg.appendChild(svgEl('circle', { cx: point.x, cy: point.y, r: 4, fill: color }));
        });

        const legendY = height - 18 + Math.floor(categoryIndex / 2) * 16;
        const legendX = margin.left + (categoryIndex % 2) * 238;
        svg.appendChild(svgEl('rect', { x: legendX, y: legendY - 10, width: 10, height: 10, fill: color }));
        const text = svgEl('text', { x: legendX + 16, y: legendY, 'text-anchor': 'start' });
        text.textContent = category;
        svg.appendChild(text);
    });

    els.categoryChart.replaceChildren(svg);
}

function polarToCartesian(cx, cy, radius, angle) {
    const radians = (angle - 90) * Math.PI / 180;
    return {
        x: cx + radius * Math.cos(radians),
        y: cy + radius * Math.sin(radians),
    };
}

function describeSlice(cx, cy, radius, startAngle, endAngle) {
    const start = polarToCartesian(cx, cy, radius, endAngle);
    const end = polarToCartesian(cx, cy, radius, startAngle);
    const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';
    return [
        `M ${cx} ${cy}`,
        `L ${start.x} ${start.y}`,
        `A ${radius} ${radius} 0 ${largeArcFlag} 0 ${end.x} ${end.y}`,
        'Z',
    ].join(' ');
}

function renderWinnerChart() {
    if (!state.filtered.length) {
        emptySvgMessage(els.winnerChart, 'No winner ratio can be calculated yet.');
        return;
    }

    const winners = state.filtered.filter((item) => Number(item.is_winner) === 1).length;
    const nominees = state.filtered.length - winners;
    const total = winners + nominees;
    const winnerAngle = total ? (winners / total) * 360 : 0;
    const width = 300;
    const height = 250;
    const cx = 120;
    const cy = 105;
    const radius = 82;

    const svg = svgEl('svg', { viewBox: `0 0 ${width} ${height}`, role: 'presentation' });
    svg.appendChild(svgEl('path', {
        d: describeSlice(cx, cy, radius, 0, winnerAngle || 0.001),
        fill: '#c99a3f',
    }));
    svg.appendChild(svgEl('path', {
        d: describeSlice(cx, cy, radius, winnerAngle, 360),
        fill: '#366c9f',
    }));
    svg.appendChild(svgEl('circle', { cx, cy, r: 44, fill: '#fff' }));

    const pct = svgEl('text', { x: cx, y: cy, 'text-anchor': 'middle', 'font-size': 22, 'font-weight': 700 });
    pct.textContent = `${Math.round((winners / total) * 100)}%`;
    svg.appendChild(pct);
    const pctLabel = svgEl('text', { x: cx, y: cy + 20, 'text-anchor': 'middle' });
    pctLabel.textContent = 'winners';
    svg.appendChild(pctLabel);

    const legend = [
        ['Winners', winners, '#c99a3f'],
        ['Other nominees', nominees, '#366c9f'],
    ];
    legend.forEach(([label, count, color], index) => {
        const y = 70 + index * 34;
        svg.appendChild(svgEl('rect', { x: 220, y: y - 11, width: 12, height: 12, fill: color }));
        const text = svgEl('text', { x: 238, y, 'text-anchor': 'start' });
        text.textContent = `${label}: ${count}`;
        svg.appendChild(text);
    });

    els.winnerChart.replaceChildren(svg);
}

function renderTable() {
    els.nominationsBody.innerHTML = '';

    if (!state.filtered.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.className = 'empty-state';
        td.textContent = 'No nominations match the current filters.';
        tr.appendChild(td);
        els.nominationsBody.appendChild(tr);
        return;
    }

    for (const nomination of state.filtered) {
        const tr = document.createElement('tr');
        const cells = [
            nomination.year,
            nomination.category,
            nomination.actor,
            `${nomination.film} (${nomination.film_year})`,
        ];

        for (const value of cells) {
            const td = document.createElement('td');
            td.textContent = value;
            tr.appendChild(td);
        }

        const actorCell = tr.children[2];
        actorCell.textContent = '';
        const actorButton = document.createElement('button');
        actorButton.type = 'button';
        actorButton.textContent = nomination.actor;
        actorButton.addEventListener('click', () => showActorDetail(nomination.actor_id));
        actorCell.appendChild(actorButton);

        const filmCell = tr.children[3];
        filmCell.textContent = '';
        const filmButton = document.createElement('button');
        filmButton.type = 'button';
        filmButton.textContent = `${nomination.film} (${nomination.film_year})`;
        filmButton.addEventListener('click', () => showFilmDetail(nomination.film_id));
        filmCell.appendChild(filmButton);

        const resultTd = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = Number(nomination.is_winner) === 1 ? 'badge win' : 'badge nominee';
        badge.textContent = Number(nomination.is_winner) === 1 ? 'Winner' : 'Nominee';
        resultTd.appendChild(badge);
        tr.appendChild(resultTd);
        els.nominationsBody.appendChild(tr);
    }
}

function renderDashboard() {
    applyFilters();
    renderMetrics();
    renderYearChart();
    renderCategoryChart();
    renderWinnerChart();
    renderTable();
}

async function showActorDetail(actorId) {
    els.detailState.textContent = 'Loading';
    els.actorDetail.className = 'actor-detail empty-state';
    els.actorDetail.textContent = 'Loading actor detail from TMDb...';

    try {
        const actor = await apiGet(`/actors/${actorId}`);
        let tmdb = null;
        let tmdbError = null;

        try {
            const enriched = await apiGet(`/enrich/actor/${actorId}`);
            tmdb = enriched.tmdb || null;
        } catch (err) {
            tmdbError = err.message;
        }

        renderActorDetail(actor, tmdb, tmdbError);
        els.detailState.textContent = tmdb ? 'Local + TMDb' : 'Local data';
    } catch (err) {
        els.detailState.textContent = 'Error';
        els.actorDetail.className = 'actor-detail error-state';
        els.actorDetail.textContent = err.message;
    }
}

async function showFilmDetail(filmId) {
    els.detailState.textContent = 'Loading';
    els.actorDetail.className = 'actor-detail empty-state';
    els.actorDetail.textContent = 'Loading film detail from TMDb...';

    try {
        const film = await apiGet(`/films/${filmId}`);
        let tmdb = null;
        let tmdbError = null;

        try {
            const enriched = await apiGet(`/enrich/film/${filmId}`);
            tmdb = enriched.tmdb || null;
        } catch (err) {
            tmdbError = err.message;
        }

        renderFilmDetail(film, tmdb, tmdbError);
        els.detailState.textContent = tmdb ? 'Local + TMDb' : 'Local data';
    } catch (err) {
        els.detailState.textContent = 'Error';
        els.actorDetail.className = 'actor-detail error-state';
        els.actorDetail.textContent = err.message;
    }
}

function renderActorDetail(actor, tmdb, tmdbError = null) {
    els.actorDetail.className = 'actor-detail';
    els.actorDetail.innerHTML = '';

    const main = document.createElement('div');
    main.className = 'actor-main';

    if (tmdb && tmdb.profile_path) {
        const img = document.createElement('img');
        img.src = `${TMDB_IMAGE_BASE}${tmdb.profile_path}`;
        img.alt = `${actor.name} portrait`;
        main.appendChild(img);
    }

    const summary = document.createElement('div');
    const title = document.createElement('h3');
    title.textContent = actor.name;
    summary.appendChild(title);

    const dl = document.createElement('dl');
    appendTerm(dl, 'Nominations', actor.nominations.length);
    appendTerm(dl, 'Wins', actor.nominations.filter((item) => Number(item.is_winner) === 1).length);
    if (tmdb) {
        appendTerm(dl, 'Birthday', tmdb.birthday || 'Unknown');
        appendTerm(dl, 'Birthplace', tmdb.place_of_birth || 'Unknown');
        appendTerm(dl, 'Known for', (tmdb.known_for_department || 'Acting'));
        appendTerm(dl, 'TMDb popularity', Math.round(tmdb.popularity || 0));
    }
    summary.appendChild(dl);
    main.appendChild(summary);
    els.actorDetail.appendChild(main);

    if (tmdb && tmdb.biography) {
        const bio = document.createElement('p');
        bio.className = 'tmdb-copy';
        bio.textContent = trimText(tmdb.biography, 420);
        els.actorDetail.appendChild(bio);
    }

    if (tmdbError) {
        appendTmdbNotice(tmdbError);
    }

    const list = document.createElement('ul');
    list.className = 'nomination-list';
    for (const nomination of actor.nominations) {
        const item = document.createElement('li');
        const result = Number(nomination.is_winner) === 1 ? 'Winner' : 'Nominee';
        item.textContent = `${nomination.year} - ${nomination.category}, ${nomination.film} (${result})`;
        list.appendChild(item);
    }
    els.actorDetail.appendChild(list);
}

function renderFilmDetail(film, tmdb, tmdbError = null) {
    els.actorDetail.className = 'actor-detail';
    els.actorDetail.innerHTML = '';

    const main = document.createElement('div');
    main.className = 'actor-main';

    if (tmdb && tmdb.poster_path) {
        const img = document.createElement('img');
        img.src = `${TMDB_POSTER_BASE}${tmdb.poster_path}`;
        img.alt = `${film.title} poster`;
        main.appendChild(img);
    }

    const summary = document.createElement('div');
    const title = document.createElement('h3');
    title.textContent = `${film.title} (${film.year || 'Unknown year'})`;
    summary.appendChild(title);

    const dl = document.createElement('dl');
    appendTerm(dl, 'Nominations', film.nominations.length);
    appendTerm(dl, 'Wins', film.nominations.filter((item) => Number(item.is_winner) === 1).length);
    if (tmdb) {
        appendTerm(dl, 'Release', tmdb.release_date || 'Unknown');
        appendTerm(dl, 'Rating', tmdb.vote_average ? `${Number(tmdb.vote_average).toFixed(1)} / 10` : 'Unknown');
        appendTerm(dl, 'Runtime', tmdb.runtime ? `${tmdb.runtime} min` : 'Unknown');
    }
    summary.appendChild(dl);
    main.appendChild(summary);
    els.actorDetail.appendChild(main);

    if (tmdb && tmdb.overview) {
        const overview = document.createElement('p');
        overview.className = 'tmdb-copy';
        overview.textContent = tmdb.overview;
        els.actorDetail.appendChild(overview);
    }

    if (tmdbError) {
        appendTmdbNotice(tmdbError);
    }

    const list = document.createElement('ul');
    list.className = 'nomination-list';
    for (const nomination of film.nominations) {
        const item = document.createElement('li');
        const result = Number(nomination.is_winner) === 1 ? 'Winner' : 'Nominee';
        item.textContent = `${nomination.year} - ${nomination.category}, ${nomination.actor} (${result})`;
        list.appendChild(item);
    }
    els.actorDetail.appendChild(list);
}

function trimText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return `${text.slice(0, maxLength).trim()}...`;
}

function appendTmdbNotice(message) {
    const notice = document.createElement('p');
    notice.className = 'tmdb-notice';
    notice.textContent = `TMDb enrichment is configured but did not respond here: ${message}`;
    els.actorDetail.appendChild(notice);
}

function appendTerm(dl, term, description) {
    const dt = document.createElement('dt');
    dt.textContent = term;
    const dd = document.createElement('dd');
    dd.textContent = description;
    dl.append(dt, dd);
}

function downloadFile(filename, mimeType, content) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

function exportCsv() {
    const headers = ['year', 'category', 'actor', 'film', 'film_year', 'is_winner'];
    const rows = state.filtered.map((item) => headers.map((key) => {
        const value = String(item[key] ?? '');
        return `"${value.replaceAll('"', '""')}"`;
    }).join(','));

    downloadFile('awa-nominations.csv', 'text/csv;charset=utf-8', [headers.join(','), ...rows].join('\n'));
}

function exportJson() {
    downloadFile('awa-nominations.json', 'application/json;charset=utf-8', JSON.stringify(state.filtered, null, 2));
}

async function init() {
    try {
        const [meta, nominations, actors, categories, editions] = await Promise.all([
            apiGet(''),
            apiGet('/nominations'),
            apiGet('/actors'),
            apiGet('/categories'),
            apiGet('/awards-editions'),
        ]);

        state.nominations = nominations;
        state.actors = actors;
        state.categories = categories;
        state.editions = editions;

        setApiState('online', `${meta.name} v${meta.version}`);
        setupFilters();
        renderDashboard();
    } catch (err) {
        setApiState('offline', 'API unavailable');
        els.nominationsBody.innerHTML = '';
        emptySvgMessage(els.yearChart, 'Start the PHP API on localhost:8000 to load award data.');
        emptySvgMessage(els.categoryChart, 'Charts will render after the API responds.');
        emptySvgMessage(els.winnerChart, 'Winner ratio is waiting for API data.');
        els.actorDetail.className = 'actor-detail error-state';
        els.actorDetail.textContent = err.message;
    }
}

els.exportCsv.addEventListener('click', exportCsv);
els.exportJson.addEventListener('click', exportJson);

init();
