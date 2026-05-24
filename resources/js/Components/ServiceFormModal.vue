<script setup>
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    service: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'save']);

const form = ref({
    id: null,
    name: '',
    duration_minutes: 60,
    price: '',
    is_active: true,
});

const errors = ref({});
const isSubmitting = ref(false);

const isEdit = computed(() => !!props.service?.id);

const modalTitle = computed(() => {
    return isEdit.value ? 'Editar Serviço' : 'Novo Serviço';
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
        if (props.service) {
            populateForm(props.service);
        }
    }
});

const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        duration_minutes: 60,
        price: '',
        is_active: true,
    };
    errors.value = {};
};

const populateForm = (service) => {
    form.value = {
        id: service.id,
        name: service.name || '',
        duration_minutes: service.duration_minutes || 60,
        price: service.price ? (service.price / 100).toString() : '',
        is_active: service.is_active !== false,
    };
};

const validateForm = () => {
    errors.value = {};

    if (!form.value.name || !form.value.name.trim()) {
        errors.value.name = 'Nome é obrigatório';
    }
    if (!form.value.duration_minutes || form.value.duration_minutes < 1) {
        errors.value.duration_minutes = 'Duração inválida';
    }
    if (form.value.price === '' || form.value.price === null) {
        errors.value.price = 'Preço é obrigatório';
    } else if (parseFloat(form.value.price) < 0) {
        errors.value.price = 'Preço não pode ser negativo';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = () => {
    if (!validateForm()) return;

    isSubmitting.value = true;
    const data = {
        name: form.value.name,
        duration_minutes: parseInt(form.value.duration_minutes),
        price: Math.round(parseFloat(form.value.price) * 100),
        is_active: form.value.is_active,
    };
    if (form.value.id) {
        data.id = form.value.id;
    }
    emit('save', data);
    isSubmitting.value = false;
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" :max-width="'md'" @close="handleClose">
        <div class="p-6" data-testid="service-modal">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600"
                    @click="handleClose"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <InputLabel for="name" value="Nome do Serviço *" />
                    <TextInput
                        id="name"
                        data-testid="name-input"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        placeholder="Ex: Corte Feminino"
                    />
                    <InputError v-if="errors.name" :message="errors.name" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="duration" value="Duração (minutos) *" />
                        <select
                            id="duration"
                            data-testid="duration-input"
                            v-model="form.duration_minutes"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option :value="15">15 min</option>
                            <option :value="30">30 min</option>
                            <option :value="45">45 min</option>
                            <option :value="60">1 hora</option>
                            <option :value="90">1h 30min</option>
                            <option :value="120">2 horas</option>
                            <option :value="180">3 horas</option>
                        </select>
                        <InputError v-if="errors.duration_minutes" :message="errors.duration_minutes" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="price" value="Preço (R$) *" />
                        <TextInput
                            id="price"
                            type="number"
                            data-testid="price-input"
                            step="0.01"
                            v-model="form.price"
                            class="mt-1 block w-full"
                            placeholder="0.00"
                        />
                        <InputError v-if="errors.price" :message="errors.price" class="mt-2" />
                    </div>
                </div>

                <div v-if="isEdit" class="flex items-center">
                    <input
                        id="is_active"
                        type="checkbox"
                        data-testid="is-active-input"
                        v-model="form.is_active"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Serviço ativo</label>
                </div>

                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    <PrimaryButton type="submit" data-testid="submit-button" :disabled="isSubmitting">
                        {{ isEdit ? 'Salvar' : 'Criar Serviço' }}
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="handleClose">
                        Cancelar
                    </SecondaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>