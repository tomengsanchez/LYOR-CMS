(function() {
    var cfg = window.dashboardIndexConfig || {};
    var apiUrl = cfg.apiUrl || '';
    var statusLabels = cfg.statusLabels || {};
    var charts = {};
    /** Match Core\Controller::normalizeApiEnvelope — { success, data, error }. */
    function unwrapApiPayload(resp) {
        if (resp && typeof resp === "object" && Object.prototype.hasOwnProperty.call(resp, "success")) {
            if (resp.success === false) return null;
            if (Object.prototype.hasOwnProperty.call(resp, "data")) return resp.data || {};
        }
        return resp || {};
    }
    function buildApiUrl() {
        var url = apiUrl;
        var fromEl = document.getElementById("dashboardDateFrom");
        var toEl = document.getElementById("dashboardDateTo");
        var params = [];
        if (fromEl && fromEl.value) params.push("date_from=" + encodeURIComponent(fromEl.value));
        if (toEl && toEl.value) params.push("date_to=" + encodeURIComponent(toEl.value));
        if (params.length) {
            url += (url.indexOf("?") >= 0 ? "&" : "?") + params.join("&");
        }
        return url;
    }
    function fetchData() {
        fetch(buildApiUrl(), { credentials: "same-origin" })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (resp) {
                var payload = unwrapApiPayload(resp);
                if (payload === null) return Promise.reject();
                if (payload.date_from && document.getElementById("dashboardDateFrom")) {
                    document.getElementById("dashboardDateFrom").value = payload.date_from;
                }
                if (payload.date_to && document.getElementById("dashboardDateTo")) {
                    document.getElementById("dashboardDateTo").value = payload.date_to;
                }
                renderDashboard(payload || {});
            })
            .catch(function () {
                var els = document.querySelectorAll("#mainDashboard .text-muted.small");
                els.forEach(function(el) { if (el.textContent === "Loading…") el.textContent = "Failed to load"; });
            });
    }
    function renderDashboard(d) {
        var p = d.profile || {}, s = d.structure || {}, g = d.grievance || {}, u = d.users || [];
        if (document.getElementById("profile-created")) { document.getElementById("profile-created").textContent = (p.created || 0).toLocaleString(); document.getElementById("profile-updated").textContent = (p.updated || 0).toLocaleString(); document.getElementById("profile-added-structures").textContent = (p.added_structures || 0).toLocaleString(); updateChartProfile(p); }
        if (document.getElementById("structure-created")) { document.getElementById("structure-created").textContent = (s.created || 0).toLocaleString(); document.getElementById("structure-updated").textContent = (s.updated || 0).toLocaleString(); document.getElementById("structure-added-images").textContent = (s.added_images || 0).toLocaleString(); updateChartStructure(s); }
        if (document.getElementById("grievance-created")) {
            document.getElementById("grievance-created").textContent = (g.unread_new || 0).toLocaleString();
            document.getElementById("grievance-updated").textContent = (g.updated || 0).toLocaleString();
            document.getElementById("grievance-status-changed").textContent = (g.status_changed || 0).toLocaleString();
            document.getElementById("grievance-escalations").textContent = (g.escalations || 0).toLocaleString();
            var byStatus = document.getElementById("grievance-by-status");
            if (byStatus) {
                var bs = g.by_status || []; byStatus.innerHTML = "";
                if (bs.length === 0) byStatus.innerHTML = '<li class="text-muted small">No data.</li>';
                else bs.forEach(function(r){ var li=document.createElement("li"); li.className="d-flex justify-content-between align-items-center py-1"; var span=document.createElement("span"); span.textContent=statusLabels[r.status]||r.status||"-"; var badge=document.createElement("span"); badge.className="badge bg-secondary"; badge.textContent=(r.count||0).toLocaleString(); li.appendChild(span); li.appendChild(badge); byStatus.appendChild(li); });
            }
            updateChartGrievance(g);
        }
        if (document.getElementById("users-by-role-list")) {
            var ul = document.getElementById("users-by-role-list");
            ul.innerHTML = "";
            if (!u.length) ul.innerHTML = '<li class="text-muted small">No data.</li>';
            else u.forEach(function(r){ var li=document.createElement("li"); li.className="d-flex justify-content-between align-items-center py-1"; var span=document.createElement("span"); span.textContent=r.role||"-"; var badge=document.createElement("span"); badge.className="badge bg-secondary"; badge.textContent=(r.count||0).toLocaleString(); li.appendChild(span); li.appendChild(badge); ul.appendChild(li); });
            updateChartUsers(u);
        }
    }
    function updateChartProfile(p){ var canvas=document.getElementById("chartProfile"); if(!canvas) return; if(charts.profile) charts.profile.destroy(); var c=p.created||0,u=p.updated||0,a=p.added_structures||0; if(c+u+a===0){c=1;u=1;a=1;} charts.profile=new Chart(canvas,{type:"bar",data:{labels:["Created","Updated","Added structures"],datasets:[{label:"Count",data:[c,u,a],backgroundColor:["#198754","#0d6efd","#0dcaf0"]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}}); }
    function updateChartStructure(s){ var canvas=document.getElementById("chartStructure"); if(!canvas) return; if(charts.structure) charts.structure.destroy(); var c=s.created||0,u=s.updated||0,a=s.added_images||0; if(c+u+a===0){c=1;u=1;a=1;} charts.structure=new Chart(canvas,{type:"bar",data:{labels:["Created","Updated","Images added"],datasets:[{label:"Count",data:[c,u,a],backgroundColor:["#198754","#0d6efd","#0dcaf0"]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}}); }
    function updateChartGrievance(g){ var canvas=document.getElementById("chartGrievance"); if(!canvas) return; if(charts.grievance) charts.grievance.destroy(); var bs=g.by_status||[]; var labels=bs.map(function(r){return statusLabels[r.status]||r.status||"-";}); var data=bs.map(function(r){return r.count||0;}); var colors=bs.map(function(r){var st=r.status||""; if(st==="open") return "#198754"; if(st==="in_progress") return "#0d6efd"; return "#6c757d";}); if(labels.length===0){labels=["No data"];data=[1];colors=["#dee2e6"];} charts.grievance=new Chart(canvas,{type:"doughnut",data:{labels:labels,datasets:[{data:data,backgroundColor:colors}]},options:{responsive:true,maintainAspectRatio:false}}); }
    function updateChartUsers(u){ var canvas=document.getElementById("chartUsers"); if(!canvas) return; if(charts.users) charts.users.destroy(); var labels=u.map(function(r){return r.role||"-";}); var data=u.map(function(r){return r.count||0;}); var colors=["#0d6efd","#198754","#fd7e14","#6f42c1","#20c997","#e83e8c"]; if(labels.length===0){labels=["No data"];data=[1];colors=["#dee2e6"];} charts.users=new Chart(canvas,{type:"doughnut",data:{labels:labels,datasets:[{data:data,backgroundColor:colors}]},options:{responsive:true,maintainAspectRatio:false}}); }
    var filterForm = document.getElementById("dashboardDateFilter");
    if (filterForm) {
        filterForm.addEventListener("submit", function(e) {
            e.preventDefault();
            fetchData();
        });
    }
    var clearBtn = document.getElementById("dashboardDateClear");
    if (clearBtn) {
        clearBtn.addEventListener("click", function() {
            var fromEl = document.getElementById("dashboardDateFrom");
            var toEl = document.getElementById("dashboardDateTo");
            if (fromEl) fromEl.value = "";
            if (toEl) toEl.value = "";
            fetchData();
        });
    }
    fetchData();
})();
