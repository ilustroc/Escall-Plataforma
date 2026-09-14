import { reactive, ref } from 'vue';
import axios from '../bootstrap';

export function useApiForm() {
    const busy = ref(false), message = ref(''), success = ref(''), errors = reactive({});
    const clearErrors = () => { Object.keys(errors).forEach(key => delete errors[key]); message.value = ''; success.value = ''; };
    async function submit(url, data, options = {}) {
        if (busy.value) return null;
        clearErrors(); busy.value = true;
        try {
            const response = await axios.post(url, data, options);
            if (response.data?.success === false) throw new Error(response.data.error || 'No se pudo completar la operación.');
            return response.data;
        } catch (error) {
            const payload = error.response?.data;
            for (const [key, value] of Object.entries(payload?.errors || {})) errors[key] = Array.isArray(value) ? value[0] : value;
            message.value = error.response?.status === 419 ? 'La sesión expiró. Recarga la página e inténtalo nuevamente.' : error.response?.status === 401 ? 'Inicia sesión para continuar.' : payload?.message || payload?.error || error.message || 'No se pudo completar la operación.';
            return null;
        } finally { busy.value = false; }
    }
    return { busy, message, success, errors, clearErrors, submit };
}
