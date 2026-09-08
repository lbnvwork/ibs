export default {
  name: 'PatientTable',
  props: {
    patients: Array,
    loading: Boolean,
    error: String,
    totalPages: Number,
    currentPage: Number,
    itemsPerPage: {
      type: Number,
      default: 30,
    },
    totalCount: {
      type: Number,
      default: 0,
    },
  },
  emits: [
    'goToPage', 'nextPage', 'prevPage', 'setItemsPerPage'
  ],
  computed: {
    pagesWindow() {
      const total = this.totalPages;
      const current = this.currentPage;
      if (!total) return [];
      if (total <= 7) {
        const out = [];
        for (let i = 1; i <= total; i += 1) out.push(i);
        return out;
      }
      const out = [1];
      if (current > 3) out.push('…');
      for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i += 1) {
        out.push(i);
      }
      if (current < total - 2) out.push('…');
      out.push(total);
      return out;
    },
    statusDefinitions() {
      return [
        { type: 'danger', label: 'Угроза кровотечения' },
        { type: 'info', label: 'Риск тромбоза' },
        // Задел под четвёртый статус (добавить при появлении сигнала):
        // { type: 'warning', label: 'Требует внимания' },
        { type: 'success', label: 'В норме' },
      ];
    },
  },
  methods: {
    triageBadge(patient) {
      const p = patient || {};
      if (p.highlightRed) return { label: 'Угроза кровотечения', type: 'danger' };
      if (p.highlightBlue) return { label: 'Риск тромбоза', type: 'info' };
      // if (p.highlightWarning) return { label: 'Требует внимания', type: 'warning' };
      return { label: 'В норме', type: 'success' };
    },
    genderIcon(sex) {
      if (sex === 1 || sex === '1') return '♂';
      if (sex === 0 || sex === '0') return '♀';
      return '—';
    },
    genderClass(sex) {
      if (sex === 1 || sex === '1') return 'gender--male';
      if (sex === 0 || sex === '0') return 'gender--female';
      return '';
    },
    statusCount(type) {
      return (this.patients || []).filter(p => this.triageBadge(p).type === type).length;
    },
  }
};