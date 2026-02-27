import axios from 'axios';
import router from '@/router';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8081/api';

const apiClient = axios.create({
    baseURL: API_BASE_URL,
    timeout: 10000,
    headers: {
        'Accept': 'application/ld+json',
        'Content-Type': 'application/json'
    }
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
})

apiClient.interceptors.response.use(
    response => response,
    error => {
        console.error('API Error:', {
            url: error.config?.url,
            method: error.config?.method,
            status: error.response?.status,
            message: error.message
        });

        if(error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            router.push('/login');
        }
        return Promise.reject(error)
    }
)

export default apiClient;