<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppointmentCard from '@/Components/AppointmentCard.vue';
import AppointmentModal from '@/Components/AppointmentModal.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    appointments: {
        type: Array,
        required: true,
    },
    selectedDate: {
        type: String,
        required: true,
    },
    professionals: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    selectedProfessionalId: {
        type: Number,
        default: null,
    },
});

const showModal = ref(false);
const editingAppointment = ref(null);
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmAppointmentId = ref(null);

const currentDate = ref(props.selectedDate);
const professionalFilter = ref(props.selectedProfessionalId || '');

const statusColors = {
    scheduled: 'border-l-4 border-l-blue-500',
    confirmed: 'border-l-4 border-l-green-500',
    in_progress: 'border-l-4 border-l-yellow-500',
    completed: 'border-l-4 border-l-gray-400',
    cancelled: 'border-l-4 border-l-red-500',
    no_show: 'border-l-4 border-l-orange-500',
};

const filteredAppointments = computed(() => {
    let filtered = props.appointments;

    if (professionalFilter.value) {
        filtered = filtered.filter(a => a.professional_id === parseInt(professionalFilter.value));
    }

    return filtered.sort((a, b) => new Date(a.start_at) - new Date(b.start_at));
});

const appointmentsByProfessional = computed(() => {
    const grouped = {};

    props.professionals.forEach(p => {
        grouped[p.id] = {
            professional: p,
            appointments: [],
        };
    });

    filteredAppointments.value.forEach(appointment => {
        if (grouped[appointment.professional_id]) {
            grouped[appointment.professional_id].appointments.push(appointment);
        } else {
            grouped[0] = grouped[0] || { professional: { id: 0, name: 'Sem profissional' }, appointments: [] };
            grouped[0].appointments.push(appointment);
        }
    });

    return Object.values(grouped).filter(g => g.appointments.length > 0 || !professionalFilter.value);
});

const professionalAppointments = computed(() => {
    if (professionalFilter.value) {
        return filteredAppointments.value;
    }
    return [];
});

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

const navigateDate = (direction) => {
    const date = new Date(currentDate.value);
    date.setDate(date.getDate() + direction);
    currentDate.value = date.toISOString().split('T')[0];
    router.get(route('dashboard.calendar.index'), {
        date: currentDate.value,
        professional: professionalFilter.value || null,
    }, { preserveState: true });
};

const goToToday = () => {
    currentDate.value = new Date().toISOString().split('T')[0];
    router.get(route('dashboard.calendar.index'), {
        date: currentDate.value,
        professional: professionalFilter.value || null,
    }, { preserveState: true });
};

const handleProfessionalFilter = () => {
    router.get(route('dashboard.calendar.index'), {
        date: currentDate.value,
        professional: professionalFilter.value || null,
    }, { preserveState: true });
};

const openCreateModal = () => {
    editingAppointment.value = null;
    showModal.value = true;
};

const openEditModal = (appointment) => {
    editingAppointment.value = appointment;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingAppointment.value = null;
};

const handleSave = (data) => {
    if (data.id) {
        router.put(route('dashboard.calendar.update', data.id), data, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        router.post(route('dashboard.calendar.store'), data, {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const confirmCancel = (appointmentId) => {
    confirmAction.value = 'cancel';
    confirmAppointmentId.value = appointmentId;
    showConfirmModal.value = true;
};

const confirmComplete = (appointmentId) => {
    confirmAction.value = 'complete';
    confirmAppointmentId.value = appointmentId;
    showConfirmModal.value = true;
};

const executeConfirmAction = () => {
    if (confirmAction.value === 'cancel') {
        router.put(route('dashboard.calendar.update', confirmAppointmentId.value), {
            status: 'cancelled',
        }, {
            onSuccess: () => {
                showConfirmModal.value = false;
            },
        });
    } else if (confirmAction.value === 'complete') {
        router.put(route('dashboard.calendar.update', confirmAppointmentId.value), {
            status: 'completed',
        }, {
            onSuccess: () => {
                showConfirmModal.value = false;
            },
        });
    }
    showConfirmModal.value = false;
};

const handleDelete = (appointmentId) => {
    if (confirm('Tem certeza que deseja excluir este agendamento?')) {
        router.delete(route('dashboard.calendar.destroy', appointmentId), {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const timeSlots = computed(() => {
    const slots = [];
    for (let hour = 8; hour <= 20; hour++) {
        slots.push({
            hour,
            label: `${hour.toString().padStart(2, '0')}:00`,
        });
    }
    return slots;
});

const getAppointmentAtSlot = (professionalId, hour) => {
    return filteredAppointments.value.find(a => {
        if (a.professional_id !== professionalId) return false;
        const appointmentHour = new Date(a.start_at).getHours();
        return appointmentHour === hour;
    });
};

const hasConflict = (professionalId, hour) => {
    const existing = getAppointmentAtSlot(professionalId, hour);
    return existing && existing.status === 'cancelled';
};
</script>

<template>
    <AppLayout title="Agenda">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Agenda
                </h2>
                <button
                    @click="openCreateModal"
                    data-testid="new-appointment-button"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Agendamento
                </button>
            </div>
        </template>

        <div class="py-4">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow p-4 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <button
                                @click="navigateDate(-1)"
                                class="p-2 rounded-md hover:bg-gray-100"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <span class="text-lg font-medium min-w-[200px] text-center">
                                {{ formatDate(currentDate) }}
                            </span>
                            <button
                                @click="navigateDate(1)"
                                class="p-2 rounded-md hover:bg-gray-100"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <button
                                @click="goToToday"
                                class="px-3 py-1 text-sm bg-gray-100 rounded-md hover:bg-gray-200"
                            >
                                Hoje
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="professional-filter" class="text-sm text-gray-600">Filtrar:</label>
                            <select
                                id="professional-filter"
                                v-model="professionalFilter"
                                @change="handleProfessionalFilter"
                                class="border-gray-300 rounded-md shadow-sm text-sm"
                            >
                                <option value="">Todos os profissionais</option>
                                <option v-for="professional in professionals" :key="professional.id" :value="professional.id">
                                    {{ professional.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div data-testid="calendar-grid" class="overflow-x-auto">
                        <div class="min-w-[800px]">
                            <div class="grid border-b border-gray-200"
                                 :style="{ gridTemplateColumns: `80px repeat(${professionals.length}, 1fr)` }"
                            >
                                <div class="p-2 text-center text-xs font-medium text-gray-500 bg-gray-50">
                                    Horário
                                </div>
                                <div
                                    v-for="professional in professionals"
                                    :key="professional.id"
                                    class="p-2 text-center text-sm font-medium text-gray-900 bg-gray-50 border-l border-gray-200"
                                >
                                    {{ professional.name }}
                                </div>
                            </div>

                            <div
                                v-for="slot in timeSlots"
                                :key="slot.hour"
                                class="grid border-b border-gray-100 hover:bg-gray-50"
                                :style="{ gridTemplateColumns: `80px repeat(${professionals.length}, 1fr)` }"
                            >
                                <div class="p-2 text-center text-xs text-gray-500 border-r border-gray-100">
                                    {{ slot.label }}
                                </div>
                                <div
                                    v-for="professional in professionals"
                                    :key="professional.id"
                                    class="p-1 border-l border-gray-200 min-h-[60px]"
                                >
                                    <template v-for="appointment in filteredAppointments" :key="appointment.id">
                                        <div
                                            :data-testid="'appointment-card-' + appointment.id"
                                            v-if="new Date(appointment.start_at).getHours() === slot.hour && appointment.professional_id === professional.id"
                                            class="cursor-pointer"
                                            @click="openEditModal(appointment)"
                                        >
                                            <AppointmentCard :appointment="appointment" :compact="true" />
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredAppointments.length === 0" class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-500">Nenhum agendamento para este dia</p>
                </div>

                <div class="mt-6 bg-white rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Legenda de Status</h3>
                    <div class="flex flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded bg-blue-100 border border-blue-200"></span>
                            <span class="text-xs text-gray-600">Agendado</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded bg-green-100 border border-green-200"></span>
                            <span class="text-xs text-gray-600">Confirmado</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded bg-yellow-100 border border-yellow-200"></span>
                            <span class="text-xs text-gray-600">Em atendimento</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded bg-gray-100 border border-gray-200"></span>
                            <span class="text-xs text-gray-600">Concluído</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded bg-red-100 border border-red-200"></span>
                            <span class="text-xs text-gray-600">Cancelado</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded bg-orange-100 border border-orange-200"></span>
                            <span class="text-xs text-gray-600">Não compareceu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AppointmentModal
            :show="showModal"
            :appointment="editingAppointment"
            :clients="clients"
            :services="services"
            :professionals="professionals"
            @close="closeModal"
            @save="handleSave"
            @cancel="confirmCancel"
            @complete="confirmComplete"
            @delete="handleDelete"
        />

        <Modal :show="showConfirmModal" data-testid="confirm-modal" :max-width="'sm'" @close="showConfirmModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Confirmar ação
                </h3>
                <p class="text-gray-600 mb-6">
                    {{ confirmAction === 'cancel' ? 'Tem certeza que deseja cancelar este agendamento?' : 'Tem certeza que deseja marcar este agendamento como concluído?' }}
                </p>
                <div class="flex justify-end gap-2">
                    <button
                        @click="showConfirmModal = false"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                    >
                        Não
                    </button>
                    <button
                        @click="executeConfirmAction"
                        data-testid="confirm-yes-button"
                        class="px-4 py-2 text-white rounded-md"
                        :class="confirmAction === 'cancel' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                    >
                        Sim
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>