@extends('layouts.app')

@section('content')
<div
  class="min-h-screen bg-neutral-50"
  x-data="auditSelect($el)"
  data-audits='@json($audits ?? [])'
  data-route-template="{{ route('audits.show', ['auditId' => '__ID__']) }}"
>
  <!-- Header -->
  <header class="bg-white border-b border-neutral-200 px-6 py-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-neutral-900">Select an Audit</h1>
        <p class="text-sm text-neutral-600 mt-1">
          Only <span class="font-medium">in-progress</span> audits within your scope are listed. Admins see all.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-3">
          <div class="text-xs font-medium text-neutral-500">Total in-progress</div>
          <div class="text-xl font-semibold text-neutral-900" x-text="filtered.length"></div>
        </div>
        <a href="{{ route('audits.select') }}"
           class="bg-neutral-900 text-white px-3 py-2 rounded-xl text-sm font-medium hover:bg-black focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
          Refresh
        </a>
      </div>
    </div>
  </header>

  <!-- ENUM CAST bug notice -->
  <div class="px-6 pt-6">
    <div x-data="{ open:true }" x-show="open" x-transition.opacity
         class="bg-neutral-100 border border-neutral-200 rounded-2xl shadow-sm p-4">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-neutral-700 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16ZM9 7h2v2H9V7Zm0 4h2v5H9v-5Z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
          <h2 class="text-sm font-medium text-neutral-900">CRITICAL: MySQL ENUM CAST Bug</h2>
          <p class="text-sm text-neutral-700 mt-1">
            Never use <code class="font-mono text-xs">CAST(enum AS UNSIGNED)</code> for weights. Use:
            <span class="font-mono text-xs bg-white border border-neutral-200 rounded px-1 py-0.5">
              CASE WHEN weight='2' THEN 2 WHEN weight='3' THEN 3 ELSE 0 END
            </span>
          </p>
        </div>
        <button type="button" @click="open=false"
                class="p-2 text-neutral-500 hover:text-neutral-700 rounded-md hover:bg-neutral-200/60 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                aria-label="Dismiss">
          <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd"
            d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 1 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 1 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"
            clip-rule="evenodd"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="px-6 pt-6">
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="space-y-1">
          <label for="search" class="block text-sm font-medium text-neutral-700">Search</label>
          <input id="search" type="text" x-model.debounce.200ms="search"
                 placeholder="Search audit #, lab, country…"
                 class="block w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                 autocomplete="off">
        </div>

        <div class="space-y-1">
          <label for="country" class="block text-sm font-medium text-neutral-700">Country</label>
          <select id="country" x-model="country"
                  class="block w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
            <option value="">All countries</option>
            <template x-for="c in countries" :key="c">
              <option :value="c" x-text="c"></option>
            </template>
          </select>
        </div>

        <div class="space-y-1">
          <label for="lab" class="block text-sm font-medium text-neutral-700">Laboratory</label>
          <select id="lab" x-model="lab"
                  class="block w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10">
            <option value="">All laboratories</option>
            <template x-for="l in labs" :key="l">
              <option :value="l" x-text="l"></option>
            </template>
          </select>
        </div>

        <div class="space-y-1">
          <label class="block text-sm font-medium text-neutral-700">Status</label>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
              In Progress
            </span>
            <span class="text-xs text-neutral-500">(completed audits are read-only in workspace)</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="px-6 pt-6 pb-10">
    <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden">
      <!-- Header -->
      <div class="bg-neutral-50 px-6 py-3 border-b border-neutral-200">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-medium text-neutral-900">In-Progress Audits</h3>
          <p class="text-xs text-neutral-500">
            <span x-text="filtered.length"></span>
            result<span x-text="filtered.length === 1 ? '' : 's'"></span>
          </p>
        </div>
      </div>

      <!-- Body -->
      <div class="divide-y divide-neutral-200">
        <template x-if="filtered.length === 0">
          <div class="px-6 py-10 text-center">
            <div class="mx-auto w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center mb-3">
              <svg class="w-4 h-4 text-neutral-500" viewBox="0 0 20 20" fill="currentColor"><path
                d="M12.9 14.32a8 8 0 1 1 1.414-1.414l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387ZM14 8a6 6 0 1 0-12 0 6 6 0 0 0 12 0Z"/></svg>
            </div>
            <p class="text-sm text-neutral-600">No matching audits. Adjust filters or keywords.</p>
          </div>
        </template>

        <template x-for="a in paged" :key="a.id">
          <div class="px-6 py-4 hover:bg-neutral-50 transition-colors duration-150">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4 items-center">
              <!-- ID / Status -->
              <div class="md:col-span-2">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-neutral-900" x-text="'#' + a.id"></span>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    In Progress
                  </span>
                </div>
                <div class="text-xs text-neutral-500 mt-1">
                  Updated <span x-text="formatDate(a.updated_at)"></span>
                </div>
              </div>

              <!-- Lab -->
              <div class="md:col-span-4">
                <div class="text-sm font-medium text-neutral-900" x-text="a.lab_name ?? '—'"></div>
                <div class="text-sm text-neutral-600">Lab No: <span x-text="a.lab_number ?? '—'"></span></div>
              </div>

              <!-- Country -->
              <div class="md:col-span-3">
                <div class="text-sm font-medium text-neutral-900" x-text="a.country_name ?? '—'"></div>
                <div class="text-xs text-neutral-500">Country</div>
              </div>

              <!-- Dates -->
              <div class="md:col-span-2">
                <div class="text-sm text-neutral-900">
                  <span class="text-neutral-500">Created:</span> <span x-text="formatDate(a.created_at)"></span>
                </div>
                <div class="text-sm text-neutral-900">
                  <span class="text-neutral-500">Updated:</span> <span x-text="formatDate(a.updated_at)"></span>
                </div>
              </div>

              <!-- Action -->
              <div class="md:col-span-1 md:text-right">
                <a :href="routeShow(a.id)"
                   class="bg-neutral-900 text-white px-3 py-2 rounded-xl text-sm font-medium hover:bg-black focus:outline-none focus:ring-2 focus:ring-neutral-900/10 inline-flex items-center justify-center w-full md:w-auto">
                  Continue
                </a>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-3 bg-white border-t border-neutral-200">
        <div class="flex items-center justify-between">
          <div class="text-xs text-neutral-500">
            Showing
            <span class="font-medium text-neutral-700" x-text="filtered.length ? ((page - 1) * perPage + 1) : 0"></span>
            to
            <span class="font-medium text-neutral-700" x-text="Math.min(page * perPage, filtered.length)"></span>
            of
            <span class="font-medium text-neutral-700" x-text="filtered.length"></span>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" @click="prev()" :disabled="page===1"
              class="bg-white text-neutral-700 border border-neutral-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 disabled:opacity-50">
              Previous
            </button>
            <button type="button" @click="next()" :disabled="(page * perPage) >= filtered.length"
              class="bg-white text-neutral-700 border border-neutral-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-900/10 disabled:opacity-50">
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Alpine component (kept here to avoid any layout collisions) --}}
<script>
  function auditSelect(el) {
    return {
      all: [],
      search: '',
      country: '',
      lab: '',
      page: 1,
      perPage: 10,
      countries: [],
      labs: [],
      filtered: [],
      paged: [],
      routeTpl: '',

      init: function () {
        var auditsAttr = el.getAttribute('data-audits') || '[]';
        try { this.all = JSON.parse(auditsAttr); } catch (e) { this.all = []; }

        this.routeTpl = el.getAttribute('data-route-template') || '';
        this.rebuildOptions();
        this.recompute();

        var self = this;
        this.$watch('search',  function () { self.page = 1; self.recompute(); });
        this.$watch('country', function () { self.page = 1; self.recompute(); });
        this.$watch('lab',     function () { self.page = 1; self.recompute(); });
        this.$watch('page',    function () { self.recomputePage(); });
      },

      rebuildOptions: function () {
        var cs = {};
        var ls = {};
        for (var i = 0; i < this.all.length; i++) {
          var a = this.all[i];
          if (a && a.country_name) cs[a.country_name] = true;
          if (a && a.lab_name)     ls[a.lab_name] = true;
        }
        this.countries = Object.keys(cs).sort();
        this.labs = Object.keys(ls).sort();
      },

      recompute: function () {
        var q = (this.search || '').toLowerCase().trim();
        function hit(v) { return (v == null ? '' : String(v)).toLowerCase().includes(q); }

        var out = [];
        for (var i = 0; i < this.all.length; i++) {
          var a = this.all[i];
          var byStatus = String(a.status || '').toLowerCase() === 'in_progress';
          var byQ = !q || hit(a.id) || hit(a.lab_name) || hit(a.lab_number) || hit(a.country_name);
          var byC = !this.country || a.country_name === this.country;
          var byL = !this.lab || a.lab_name === this.lab;
          if (byStatus && byQ && byC && byL) out.push(a);
        }
        this.filtered = out;

        var maxPage = Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        if (this.page > maxPage) this.page = maxPage;

        this.recomputePage();
      },

      recomputePage: function () {
        var start = (this.page - 1) * this.perPage;
        var end   = this.page * this.perPage;
        if (start < 0) start = 0;
        if (end < 0) end = 0;
        this.paged = this.filtered.slice(start, end);
      },

      next: function () {
        if (this.page * this.perPage < this.filtered.length) this.page++;
      },

      prev: function () {
        if (this.page > 1) this.page--;
      },

      formatDate: function (s) {
        if (!s) return '—';
        var d = new Date(s);
        if (isNaN(d)) return s;
        return d.toLocaleString(undefined, {
          year: 'numeric', month: 'short', day: '2-digit',
          hour: '2-digit', minute: '2-digit'
        });
      },

      routeShow: function (id) {
        return (this.routeTpl || '').replace('__ID__', encodeURIComponent(id));
      }
    };
  }
</script>
@endsection
