<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ClientFormModal from '@/Components/ClientFormModal.vue';

const props = defineProps({
    clients: {
        type: Array,
        required: true,
    },
    inactiveClients: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const showInactive = ref(false);
const showModal = ref(false);
const editingClient = ref(null);

const filteredClients = computed(() => {
    let list = showInactive.value ? props.inactiveClients : props.clients;
    if (!searchQuery.value) return list;
    const query = searchQuery.value.toLowerCase();
    return list.filter(c =>
        c.name.toLowerCase().includes(query) ||
        (c.phone && c.phone.includes(query)) ||
        (c.email && c.email.toLowerCase().includes(query))
    );
});

const openCreateModal = () => {
    editingClient.value = null;
    showModal.value = true;
};

const openEditModal = (client) => {
    editingClient.value = client;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingClient.value = null;
};

const handleSave = (data) => {
    if (data.id) {
        router.put(route('dashboard.clients.update', data.id), data, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post(route('dashboard.clients.store'), data, {
            onSuccess: () => closeModal(),
        });
    }
};

const handleDelete = (clientId) => {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        router.delete(route('dashboard.clients.destroy', clientId), {
            onSuccess: () => {},
        });
    }
};

const formatPhone = (phone) => {
    if (!phone) return '-';
    return phone;
};
</script>

<template>
    <AppLayout title="Clientes">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Clientes
                </h2>
                <button
                    @click="openCreateModal"
                    data-testid="new-client-button"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Cliente
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
                                placeholder="Buscar por nome, telefone ou email..."
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
                        <table class="min-w-full divide-y divide-gray-200" data-testid="clients-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Telefone
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Observações
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="filteredClients.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p>Nenhum cliente encontrado</p>
                                        <button
                                            @click="openCreateModal"
                                            class="mt-2 text-indigo-600 hover:text-indigo-800"
                                        >
                                            Cadastrar primeiro cliente
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-for="client in filteredClients"
                                    :key="client.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span class="text-indigo-600 font-medium">{{ client.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div class="ml-4">
                                                <a
                                                    :href="route('dashboard.clients.show', client.id)"
                                                    class="text-sm font-medium text-gray-900 hover:text-indigo-600"
                                                >
                                                    {{ client.name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatPhone(client.phone) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ client.email || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ client.notes || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openEditModal(client)"
                                            data-testid="edit-button"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="handleDelete(client.id)"
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
                    Mostrando {{ filteredClients.length }} cliente(s)
                </div>
            </div>
        </div>

        <ClientFormModal
            :show="showModal"
            :client="editingClient"
            @close="closeModal"
            @save="handleSave"
        />
    </AppLayout>
</template>