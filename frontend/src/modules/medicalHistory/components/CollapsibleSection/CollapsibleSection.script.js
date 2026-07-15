export default {
  name: 'CollapsibleSection',
  props: {
    title: { type: String, required: true },
    preview: { type: String, default: '' },
    expanded: { type: Boolean, default: false },
    forceExpand: { type: Boolean, default: false },
  },
  data() {
    return {
      isExpanded: this.expanded,
    };
  },
  watch: {
    forceExpand(val) {
      if (val) {
        this.isExpanded = true;
      }
    },
  },
  methods: {
    toggle() {
      this.isExpanded = !this.isExpanded;
    },
    expand() {
      this.isExpanded = true;
    },
    collapse() {
      this.isExpanded = false;
    },
  },
};