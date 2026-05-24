<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ServiceFormModal from '@/Components/ServiceFormModal.vue';

const props = defineProps({
    services: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const showInactive = ref(false);
const showModal = ref(false);
const editingService = ref(null);

const filteredServices = computed(() => {
    if (!searchQuery.value) {
        return showInactive.value ? props.services : props.services.filter(s => s.is_active !== false);
    }
    const query = searchQuery.value.toLowerCase();
    return props.services.filter(s => {
        const matchesSearch = s.name.toLowerCase().includes(query);
        const matchesInactive = showInactive.value || s.is_active !== false;
        return matchesSearch && matchesInactive;
    });
});

const openCreateModal = () => {
    editingService.value = null;
    showModal.value = true;
};

const openEditModal = (service) => {
    editingService.value = service;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingService.value = null;
};

const handleSave = (data) => {
    if (data.id) {
        router.put(route('dashboard.services.update', data.id), data, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post(route('dashboard.services.store'), data, {
            onSuccess: () => closeModal(),
        });
    }
};

const handleDelete = (serviceId) => {
    if (confirm('Tem certeza que deseja excluir este serviço?')) {
        router.delete(route('dashboard.services.destroy', serviceId), {
            onSuccess: () => {},
        });
    }
};

const formatPrice = (cents) => {
    if (cents === null || cents === undefined) return '-';
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};
</script>

<template>
    <AppLayout title="Serviços">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Serviços
                </h2>
                <button
                    @click="openCreateModal"
                    data-testid="new-service-button"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Serviço
                </button>
            </div>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow p-4 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="relative w-full sm:w-64">
                            <input
                                type="text"
                                v-model="searchQuery"
                                data-testid="search-input"
                                placeholder="Buscar serviços..."
                                class="w-full pl-10 pr-4 py-2 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center text-sm text-gray-600">
                                <input
                                    type="checkbox"
                                    v-model="showInactive"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm mr-2"
                                />
                                Mostrar inativos
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" data-testid="services-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Serviço
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Duração
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Preço
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="filteredServices.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <p>Nenhum serviço encontrado</p>
                                        <button
                                            @click="openCreateModal"
                                            class="mt-2 text-indigo-600 hover:text-indigo-800"
                                        >
                                            Cadastrar primeiro serviço
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-for="service in filteredServices"
                                    :key="service.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ service.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ service.duration_minutes }} min
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                        {{ formatPrice(service.price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            :class="service.is_active !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            class="px-2 py-1 text-xs font-medium rounded"
                                        >
                                            {{ service.is_active !== false ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openEditModal(service)"
                                            data-testid="edit-button"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="handleDelete(service.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Excluir
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-500 text-center">
                    Mostrando {{ filteredServices.length }} serviço(s)
                </div>
            </div>
        </div>

        <ServiceFormModal
            :show="showModal"
            :service="editingService"
            @close="closeModal"
            @save="handleSave"
        />
    </AppLayout>
</template>