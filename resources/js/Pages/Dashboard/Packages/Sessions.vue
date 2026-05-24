<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    package: {
        type: Object,
        required: true,
    },
    sessions: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
});

const selectedClientId = ref('');
const showPurchaseModal = ref(false);
const purchaseErrors = ref({});

const activeSessions = computed(() => {
    return props.sessions.filter(s => s.is_active !== false);
});

const expiredSessions = computed(() => {
    return props.sessions.filter(s => s.is_active === false);
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatPrice = (cents) => {
    if (cents === null || cents === undefined) return '-';
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const handlePurchase = () => {
    purchaseErrors.value = {};

    if (!selectedClientId.value) {
        purchaseErrors.value.client_id = 'Selecione um cliente';
        return;
    }

    router.post(route('dashboard.packages.purchase'), {
        package_id: props.package.id,
        client_id: selectedClientId.value,
    }, {
        onSuccess: () => {
            showPurchaseModal.value = false;
            selectedClientId.value = '';
        },
    });
};

const getClientName = (clientId) => {
    const client = props.clients.find(c => c.id === clientId);
    return client ? client.name : 'Cliente desconhecido';
};
</script>

<template>
    <AppLayout title="Gerenciar Sessões do Pacote">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ package.name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Gerenciar sessões do pacote</p>
                </div>
                <div class="flex gap-2">
                    <a
                        :href="route('dashboard.packages.index')"
                        class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Voltar
                    </a>
                    <button
                        @click="showPurchaseModal = true"
                        data-testid="purchase-button"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Vincular a Cliente
                    </button>
                </div>
            </div>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Informações do Pacote</h3>
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Preço:</span>
                                    <span class="ml-1 font-medium text-gray-900">{{ formatPrice(package.price) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Validade:</span>
                                    <span class="ml-1 font-medium text-gray-900">{{ package.valid_until_days }} dias</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Serviços:</span>
                                    <span class="ml-1 font-medium text-gray-900">{{ package.services?.length || 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Sessões ativas:</span>
                                    <span class="ml-1 font-medium text-indigo-600">{{ activeSessions.length }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="package.services && package.services.length > 0" class="mt-4 pt-4 border-t">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Serviços incluídos</h4>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="service in package.services"
                                :key="service.id"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700"
                            >
                                {{ service.name }}
                                <span class="ml-1 text-xs text-gray-500">({{ service.pivot?.session_count || service.session_count || 0 }}x)</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Sessões Ativas</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" data-testid="active-sessions-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sessões Used/Total
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data de Compra
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Expira em
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="activeSessions.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        Nenhuma sessão ativa. Vincule o pacote a um cliente para começar.
                                    </td>
                                </tr>
                                <tr v-for="session in activeSessions" :key="session.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ getClientName(session.client_id) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ session.used_sessions || 0 }} / {{ session.total_sessions }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(session.purchased_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(session.expires_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">
                                            Ativo
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="expiredSessions.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Sessões Expiradas</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sessões Used/Total
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data de Compra
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Expira em
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="session in expiredSessions" :key="session.id" class="hover:bg-gray-50 opacity-60">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ getClientName(session.client_id) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ session.used_sessions || 0 }} / {{ session.total_sessions }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(session.purchased_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(session.expires_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800">
                                            Expirado
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showPurchaseModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Vincular Pacote a Cliente</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                            <select
                                v-model="selectedClientId"
                                data-testid="client-select"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Selecione um cliente</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </select>
                            <p v-if="purchaseErrors.client_id" class="mt-1 text-sm text-red-600">{{ purchaseErrors.client_id }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="handlePurchase"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Vincular
                        </button>
                        <button
                            @click="showPurchaseModal = false"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>