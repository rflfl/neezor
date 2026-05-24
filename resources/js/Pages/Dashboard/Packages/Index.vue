<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PackageFormModal from '@/Components/PackageFormModal.vue';

const props = defineProps({
    packages: {
        type: Array,
        required: true,
    },
    services: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const showModal = ref(false);
const editingPackage = ref(null);

const filteredPackages = computed(() => {
    if (!searchQuery.value) return props.packages;
    const query = searchQuery.value.toLowerCase();
    return props.packages.filter(p =>
        p.name.toLowerCase().includes(query) ||
        (p.services && p.services.some(s => s.name.toLowerCase().includes(query)))
    );
});

const openCreateModal = () => {
    editingPackage.value = null;
    showModal.value = true;
};

const openEditModal = (pkg) => {
    editingPackage.value = pkg;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPackage.value = null;
};

const handleSave = (data) => {
    if (data.id) {
        router.put(route('dashboard.packages.update', data.id), data, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post(route('dashboard.packages.store'), data, {
            onSuccess: () => closeModal(),
        });
    }
};

const handleDelete = (packageId) => {
    if (confirm('Tem certeza que deseja excluir este pacote?')) {
        router.delete(route('dashboard.packages.destroy', packageId), {
            onSuccess: () => {},
        });
    }
};

const formatPrice = (cents) => {
    if (cents === null || cents === undefined) return '-';
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const getTotalSessions = (pkg) => {
    if (!pkg.services || pkg.services.length === 0) return 0;
    return pkg.services.reduce((sum, s) => sum + (s.pivot?.session_count || s.session_count || 0), 0);
};

const getServicesPreview = (pkg) => {
    if (!pkg.services || pkg.services.length === 0) return 'Nenhum serviço';
    return pkg.services.map(s => `${s.name} (${s.pivot?.session_count || s.session_count || 0})`).join(', ');
};
</script>

<template>
    <AppLayout title="Pacotes">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Pacotes
                </h2>
                <button
                    @click="openCreateModal"
                    data-testid="new-package-button"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Pacote
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
                                placeholder="Buscar pacotes..."
                                class="w-full pl-10 pr-4 py-2 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div v-if="filteredPackages.length === 0" class="col-span-full bg-white rounded-lg shadow p-12 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p>Nenhum pacote encontrado</p>
                        <button
                            @click="openCreateModal"
                            class="mt-2 text-indigo-600 hover:text-indigo-800"
                        >
                            Criar primeiro pacote
                        </button>
                    </div>

                    <div
                        v-for="pkg in filteredPackages"
                        :key="pkg.id"
                        class="bg-white rounded-lg shadow overflow-hidden hover:shadow-md transition"
                    >
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ pkg.name }}</h3>
                                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ formatPrice(pkg.price) }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                        {{ pkg.valid_until_days }} dias
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="text-xs font-medium text-gray-500 uppercase mb-1">Serviços ({{ getTotalSessions(pkg) }} sessões)</div>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ getServicesPreview(pkg) }}</p>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t pt-3">
                                <a
                                    :href="route('dashboard.packages.sessions', pkg.id)"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    Gerenciar sessões →
                                </a>
                                <div class="flex gap-2">
                                    <button
                                        @click="openEditModal(pkg)"
                                        data-testid="edit-button"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="handleDelete(pkg.id)"
                                        class="text-red-600 hover:text-red-900 text-sm"
                                    >
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-500 text-center">
                    Mostrando {{ filteredPackages.length }} pacote(s)
                </div>
            </div>
        </div>

        <PackageFormModal
            :show="showModal"
            :package="editingPackage"
            :available-services="services"
            @close="closeModal"
            @save="handleSave"
        />
    </AppLayout>
</template>