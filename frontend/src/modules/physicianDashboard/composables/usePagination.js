import { ref, watch } from 'vue';

export function usePagination(store) {
    const pageInput = ref(store.currentPage || 1);

    watch(() => store.currentPage, (newVal) => {
        pageInput.value = newVal;
    });

    function goToPage(page) {
        let num = parseInt(page);
        if (isNaN(num)) num = 1;
        num = Math.min(Math.max(num, 1), store.totalPages);
        if (num !== store.currentPage) {
            store.setPage(num);
        }
    }

    return { pageInput, goToPage };
}