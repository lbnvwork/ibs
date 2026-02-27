import { reactive, ref, computed } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';

export default {
    setup() {
        const authStore = useAuthStore();
        const {loading, error} = storeToRefs(authStore);

        const form = reactive({
            login: '',
            password: ''
        });

        const handleSubmit = async () => {
            await authStore.login(form);
        };

        return {
            form,
            loading,
            error,
            handleSubmit
        };
    }
}