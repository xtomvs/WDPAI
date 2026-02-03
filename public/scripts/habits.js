// 🔨🤖🔧 Nawyki: render listy, filtrowanie, toggle dni tygodnia, CRUD (PHP API)

const $ = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

/**
 * USTAW na true, gdy chcesz ładować dane z bazy PHP API
 */
const USE_API = true;

const DAYS = ["Pn","Wt","Śr","Cz","Pt","So","Nd"];

// mapowanie indeksu dnia (0=Pn) -> data (YYYY-MM-DD) dla bieżącego tygodnia
function getWeekDates(baseDate = new Date()){
  // ustaw na poniedziałek
  const d = new Date(baseDate);
  const day = (d.getDay() + 6) % 7; // pon=0, nd=6
  d.setDate(d.getDate() - day);
  d.setHours(0,0,0,0);

  const out = [];
  for(let i=0;i<7;i++){
    const x = new Date(d);
    x.setDate(d.getDate() + i);
    out.push(toISODate(x));
  }
  return out;
}
function toISODate(d){
  const y = d.getFullYear();
  const m = String(d.getMonth()+1).padStart(2,"0");
  const day = String(d.getDate()).padStart(2,"0");
  return `${y}-${m}-${day}`;
}

const icons = {
  study: `<span class="material-symbols-outlined">menu_book</span>`,
  fitness:`<span class="material-symbols-outlined">fitness_center</span>`,
  meditate:`<span class="material-symbols-outlined">self_improvement</span>`,
  fire:`<span class="material-symbols-outlined">local_fire_department</span>`,
  check:`<span class="material-symbols-outlined">check</span>`,
  edit:`<span class="material-symbols-outlined">edit</span>`,
  more:`<span class="material-symbols-outlined">more_horiz</span>`,
  close:`<span class="material-symbols-outlined">close</span>`
};

// ------ MOCK DATA (gdy USE_API=false) ------
let db = {
  user: { name:"Jan Kowalski", email:"j.kowalski@email.com" },
  stats: { todayDone:0, todayTotal:0, longestStreakDays:0, points:0, pointsDeltaPct:0 },
  habits: []
};

// ------ State UI ------
let activeFilter = "all";
const weekDates = getWeekDates(new Date()); // bieżący tydzień (ISO dates)

// ------ API helpers ------
async function apiGetHabits(){
  const res = await fetch(`/api/habits`, {
    headers: { "Accept":"application/json" },
    credentials: "same-origin"
  });
  if(!res.ok) throw new Error("API /api/habits failed");
  return res.json();
}

async function apiGetStats(){
  const res = await fetch(`/api/habits/stats`, {
    headers: { "Accept":"application/json" },
    credentials: "same-origin"
  });
  if(!res.ok) throw new Error("API /api/habits/stats failed");
  return res.json();
}

async function apiToggleDay(habitId, dateISO){
  const res = await fetch(`/api/habits/${habitId}/toggle`, {
    method: "POST",
    headers: { "Content-Type":"application/json", "Accept":"application/json" },
    credentials: "same-origin",
    body: JSON.stringify({ date: dateISO })
  });
  if(!res.ok) throw new Error("API toggle failed");
  return res.json();
}

async function apiCreateHabit(payload){
  const res = await fetch(`/api/habits`, {
    method:"POST",
    headers: { "Content-Type":"application/json", "Accept":"application/json" },
    credentials: "same-origin",
    body: JSON.stringify(payload)
  });
  if(!res.ok) throw new Error("API create habit failed");
  return res.json();
}

async function apiUpdateHabit(habitId, payload){
  const res = await fetch(`/api/habits/${habitId}`, {
    method:"PUT",
    headers: { "Content-Type":"application/json", "Accept":"application/json" },
    credentials: "same-origin",
    body: JSON.stringify(payload)
  });
  if(!res.ok) throw new Error("API update habit failed");
  return res.json();
}

async function apiDeleteHabit(habitId){
  const res = await fetch(`/api/habits/${habitId}`, {
    method:"DELETE",
    headers: { "Accept":"application/json" },
    credentials: "same-origin"
  });
  if(!res.ok) throw new Error("API delete habit failed");
  return res.json();
}

// ------ Render ------
function escapeHtml(s){
  return String(s)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

function accentToBadgeClass(accent){
  if(["blue","green","purple","orange"].includes(accent)) return accent;
  return "blue";
}

function renderStats(stats){
  const statToday = $("#statToday");
  const statTodayPct = $("#statTodayPct");
  const statStreak = $("#statStreak");
  const statPoints = $("#statPoints");
  const statPointsDelta = $("#statPointsDelta");
  
  if (statToday) statToday.textContent = `${stats.todayDone} / ${stats.todayTotal}`;
  const pct = stats.todayTotal ? Math.round((stats.todayDone / stats.todayTotal) * 100) : 0;
  if (statTodayPct) statTodayPct.textContent = `${pct}%`;
  if (statStreak) statStreak.textContent = `${stats.longestStreakDays} dni`;
  if (statPoints) statPoints.textContent = Number(stats.points).toLocaleString("pl-PL");
  if (statPointsDelta) statPointsDelta.textContent = `${stats.pointsDeltaPct >= 0 ? "+" : ""}${stats.pointsDeltaPct}%`;
}

function matchesFilter(h){
  if(activeFilter === "all") return true;
  return h.category === activeFilter;
}

function renderHabits(habits){
  const root = $("#habitsList");
  root.innerHTML = "";

  const filtered = habits.filter(matchesFilter);

  filtered.forEach(h => {
    const card = document.createElement("article");
    card.className = "habit-card";
    card.dataset.habitId = String(h.id);

    const badgeClass = accentToBadgeClass(h.accent);
    const icon = icons[h.icon] ?? icons.study;

    card.innerHTML = `
      <div class="habit-row">
        <div class="habit-left">
          <div class="badge ${badgeClass}" aria-hidden="true">${icon}</div>
          <div class="habit-info">
            <h3 class="habit-title">${escapeHtml(h.title)}</h3>
            <p class="habit-meta">Kategoria: ${escapeHtml(capitalize(h.category))} • ${escapeHtml(h.frequencyLabel)}</p>
            <div class="streak" title="Seria">
              ${icons.fire}
              <span>Seria: ${escapeHtml(h.streakDays)} dni</span>
            </div>
          </div>
        </div>

        <div class="habit-right">
          <div class="week" aria-label="Tydzień">
            ${renderWeekDots(h)}
          </div>

          <div class="card-actions">
            <button class="small-btn" type="button" data-action="edit" aria-label="Edytuj">${icons.edit}</button>
            <button class="small-btn" type="button" data-action="more" aria-label="Więcej">${icons.more}</button>
          </div>
        </div>
      </div>
    `;

    // bind: toggle day
    $$(".dotbtn", card).forEach(btn => {
      const idx = Number(btn.dataset.dayIndex);
      const status = btn.dataset.status;
      const dateISO = btn.dataset.date;
      if(status === "disabled") return;

      btn.addEventListener("click", async () => {
        // UI optimistic
        const weekData = h.week || [];
        const prev = weekData[idx]?.done ? "done" : "todo";
        if (weekData[idx]) {
          weekData[idx].done = !weekData[idx].done;
        }
        renderHabits(db.habits);

        try{
          if(USE_API){
            await apiToggleDay(h.id, dateISO);
            await refreshFromApi();
          }else{
            recomputeStatsMock();
          }
        }catch(err){
          // rollback
          if (weekData[idx]) {
            weekData[idx].done = prev === "done";
          }
          renderHabits(db.habits);
          alert("Nie udało się zapisać w bazie: " + err.message);
        }
      });
    });

    // bind: edit
    $("[data-action='edit']", card).addEventListener("click", () => openHabitModal(h));
    // bind: more (delete demo)
    $("[data-action='more']", card).addEventListener("click", () => openMoreMenu(h));

    root.appendChild(card);
  });

  if(filtered.length === 0){
    const empty = document.createElement("div");
    empty.style.color = "#64748b";
    empty.style.fontWeight = "700";
    empty.style.padding = "10px 2px";
    empty.textContent = "Brak nawyków dla wybranego filtra.";
    root.appendChild(empty);
  }
}

function renderWeekDots(h){
  // h.week z API to tablica obiektów: [{day, date, done}, ...]
  const weekData = Array.isArray(h.week) ? h.week : [];
  
  return DAYS.map((d, idx) => {
    let st = "todo";
    if (weekData[idx]) {
      st = weekData[idx].done ? "done" : "todo";
    }
    const cls = st === "done" ? "done" : st === "disabled" ? "disabled" : "todo";
    const dateISO = weekData[idx]?.date || weekDates[idx];
    
    return `
      <div class="day">
        <div class="lbl">${d}</div>
        <button
          class="dotbtn ${cls}"
          type="button"
          data-day-index="${idx}"
          data-status="${st}"
          data-date="${dateISO}"
          aria-label="${d} — ${st === "done" ? "zrobione" : st === "disabled" ? "nie dotyczy" : "do zrobienia"}"
          ${st === "disabled" ? "disabled" : ""}
        >
          ${icons.check}
        </button>
      </div>
    `;
  }).join("");
}

function capitalize(s){
  if(!s) return "";
  return s[0].toUpperCase() + s.slice(1);
}

// ------ Filters ------
function setupTabs(){
  $$(".tab").forEach(btn => {
    btn.addEventListener("click", () => {
      $$(".tab").forEach(x => x.classList.remove("active"));
      btn.classList.add("active");
      activeFilter = btn.dataset.filter;
      renderHabits(db.habits);
    });
  });
}

// ------ Modal (create/edit) ------
function openModal(){
  const m = $("#habitModal");
  m.classList.add("open");
  m.setAttribute("aria-hidden","false");
}
function closeModal(){
  const m = $("#habitModal");
  m.classList.remove("open");
  m.setAttribute("aria-hidden","true");
}

function resetForm(){
  $("#habitId").value = "";
  $("#habitTitle").value = "";
  $("#habitCategory").value = "studia";
  $("#habitFrequency").value = "daily";
  $("#habitAccent").value = "blue";
  $("#habitPoints").value = "10";
}

function openHabitModal(habit){
  resetForm();
  if(habit){
    $("#modalTitle").textContent = "Edytuj nawyk";
    $("#habitId").value = String(habit.id);
    $("#habitTitle").value = habit.title;
    $("#habitCategory").value = habit.category;
    $("#habitFrequency").value = toFreqValue(habit.frequencyLabel);
    $("#habitAccent").value = habit.accent;
    $("#habitPoints").value = String(habit.pointsPerDay ?? 10);
  }else{
    $("#modalTitle").textContent = "Nowy nawyk";
  }
  openModal();
}

function toFreqValue(label){
  if(label === "Codziennie") return "daily";
  if(label.includes("3")) return "3x";
  return "custom";
}
function fromFreqValue(v){
  if(v === "daily") return "Codziennie";
  if(v === "3x") return "3× w tygodniu";
  return "Własny";
}

function setupModal(){
  $("#newHabitBtn").addEventListener("click", () => openHabitModal(null));

  // close handlers
  $$("#habitModal [data-close='1']").forEach(el => el.addEventListener("click", closeModal));
  document.addEventListener("keydown", (e) => {
    if(e.key === "Escape" && $("#habitModal").classList.contains("open")) closeModal();
  });

  $("#habitForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const id = $("#habitId").value ? Number($("#habitId").value) : null;
    const title = $("#habitTitle").value.trim();
    const category = $("#habitCategory").value;
    const accent = $("#habitAccent").value;
    const frequencyValue = $("#habitFrequency").value;
    const frequencyLabel = fromFreqValue(frequencyValue);
    const pointsPerDay = Number($("#habitPoints").value || 0);

    if(!title){
      alert("Podaj nazwę nawyku.");
      return;
    }

    try{
      if(USE_API){
        if(id){
          await apiUpdateHabit(id, { 
            title, 
            category, 
            accentColor: accent, 
            frequency: frequencyValue,
            pointsPerDay 
          });
        }else{
          await apiCreateHabit({ 
            title, 
            category, 
            accentColor: accent, 
            frequency: frequencyValue,
            pointsPerDay 
          });
        }
        await refreshFromApi();
      }else{
        if(id){
          const h = db.habits.find(x => x.id === id);
          if(h){
            h.title = title;
            h.category = category;
            h.accentColor = accent;
            h.frequencyLabel = frequencyLabel;
            h.pointsPerDay = pointsPerDay;
          }
        }else{
          const newId = Math.max(0, ...db.habits.map(x => x.id)) + 1;
          db.habits.unshift({
            id: newId,
            title,
            category,
            frequencyLabel,
            accentColor: accent,
            streakDays: 0,
            icon: category === "studia" ? "study" : "meditate",
            pointsPerDay,
            week: ["todo","todo","todo","todo","todo","todo","todo"]
          });
        }
        recomputeStatsMock();
      }

      renderHabits(db.habits);
      closeModal();
    }catch(err){
      alert("Nie udało się zapisać: " + err.message);
    }
  });
}

function openMoreMenu(h){
  const choice = prompt(
    `Opcje dla: ${h.title}\n\nWpisz:\n- delete (usuń)\n- points (pokaż punkty)`,
    ""
  );
  if(!choice) return;

  if(choice.toLowerCase() === "points"){
    alert(`Punkty za dzień: ${h.pointsPerDay ?? 0}`);
    return;
  }

  if(choice.toLowerCase() === "delete"){
    if(!confirm("Na pewno usunąć nawyk?")) return;
    deleteHabit(h.id).catch(err => alert("Błąd usuwania: " + err.message));
  }
}

async function deleteHabit(id){
  if(USE_API){
    await apiDeleteHabit(id);
    await refreshFromApi();
  }else{
    db.habits = db.habits.filter(h => h.id !== id);
    recomputeStatsMock();
  }
  renderHabits(db.habits);
}

// ------ Mock stats recompute (żeby demo żyło) ------
function recomputeStatsMock(){
  // "dziś" = pierwszy dzień tygodnia który odpowiada dziś (w tym demo przyjmijmy Śr -> index 2),
  // ale prościej: bierz realny dzień tygodnia
  const todayIdx = (new Date().getDay() + 6) % 7;

  let total = 0;
  let done = 0;
  let longest = 0;
  let points = 0;

  for(const h of db.habits){
    // liczymy dzisiaj tylko gdy nie disabled
    const st = h.week[todayIdx];
    if(st !== "disabled"){
      total++;
      if(st === "done"){
        done++;
        points += (h.pointsPerDay ?? 0);
      }
    }
    longest = Math.max(longest, Number(h.streakDays) || 0);
  }

  db.stats.todayDone = done;
  db.stats.todayTotal = total;
  db.stats.longestStreakDays = Math.max(db.stats.longestStreakDays, longest);
  db.stats.points = (Number(db.stats.points) || 0) + points;
  db.stats.pointsDeltaPct = db.stats.pointsDeltaPct; // demo bez zmian

  renderStats(db.stats);
}

// ------ Load/init ------
async function refreshFromApi(){
  try {
    const habitsRes = await apiGetHabits();
    const statsRes = await apiGetStats();
    
    if (habitsRes.success && habitsRes.data) {
      db.habits = habitsRes.data.map(h => ({
        id: h.id,
        title: h.title,
        category: h.category,
        frequencyLabel: h.frequencyLabel || 'Codziennie',
        accent: h.accentColor || 'blue',
        streakDays: h.streakDays || 0,
        icon: h.icon || 'check',
        pointsPerDay: h.pointsPerDay || 10,
        week: h.week || []
      }));
    }
    
    if (statsRes.success && statsRes.data) {
      db.stats = {
        todayDone: statsRes.data.todayCompleted || 0,
        todayTotal: statsRes.data.totalHabits || 0,
        longestStreakDays: statsRes.data.maxStreak || 0,
        points: statsRes.data.totalPoints || 0,
        pointsDeltaPct: 0
      };
    }
    
    renderStats(db.stats);
    renderHabits(db.habits);
  } catch(err) {
    console.error("refreshFromApi error:", err);
  }
}

function setupTopActions(){
  $("#logoutBtn").addEventListener("click", () => {
    window.location.href = "/logout";
  });
  $("#bellBtn").addEventListener("click", () => alert("Powiadomienia (demo)."));
  $("#searchBtn").addEventListener("click", () => {
    const q = prompt("Szukaj nawyku (nazwa):", "");
    if(q == null) return;
    const query = q.trim().toLowerCase();
    if(!query){
      renderHabits(db.habits);
      return;
    }
    const filtered = db.habits.filter(h => h.title.toLowerCase().includes(query));
    // tymczasowe: wyświetl tylko wyniki
    const root = $("#habitsList");
    root.innerHTML = "";
    renderHabits(filtered);
  });
}

function init(){
  // user
  $("#userName").textContent = db.user.name;
  $("#userEmail").textContent = db.user.email;

  setupTabs();
  setupModal();
  setupTopActions();

  renderStats(db.stats);
  renderHabits(db.habits);

  // na mocku też przelicz na start, żeby zgadzało się z realnym dniem tygodnia
  if(!USE_API) recomputeStatsMock();

  if(USE_API){
    refreshFromApi().catch(err => {
      console.error(err);
      alert("Nie udało się pobrać danych z API. Sprawdź /api i bazę.");
    });
  }
}

document.addEventListener("DOMContentLoaded", init);
