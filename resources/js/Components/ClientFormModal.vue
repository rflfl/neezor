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
    client: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'save']);

const form = ref({
    id: null,
    name: '',
    phone: '',
    email: '',
    notes: '',
    is_active: true,
});

const errors = ref({});
const isSubmitting = ref(false);

const isEdit = computed(() => !!props.client?.id);

const modalTitle = computed(() => {
    return isEdit.value ? 'Editar Cliente' : 'Novo Cliente';
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
        if (props.client) {
            populateForm(props.client);
        }
    }
});

const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        phone: '',
        email: '',
        notes: '',
        is_active: true,
    };
    errors.value = {};
};

const populateForm = (client) => {
    form.value = {
        id: client.id,
        name: client.name || '',
        phone: client.phone || '',
        email: client.email || '',
        notes: client.notes || '',
        is_active: client.is_active !== false,
    };
};

const validateForm = () => {
    errors.value = {};

    if (!form.value.name || !form.value.name.trim()) {
        errors.value.name = 'Nome é obrigatório';
    }
    if (form.value.email && !form.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        errors.value.email = 'Email inválido';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = () => {
    if (!validateForm()) return;

    isSubmitting.value = true;
    emit('save', { ...form.value });
    isSubmitting.value = false;
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" :max-width="'md'" @close="handleClose">
        <div class="p-6" data-testid="client-modal">
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
                    <InputLabel for="name" value="Nome *" />
                    <TextInput
                        id="name"
                        data-testid="name-input"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        placeholder="Nome completo"
                    />
                    <InputError v-if="errors.name" :message="errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="phone" value="Telefone" />
                    <TextInput
                        id="phone"
                        type="tel"
                        data-testid="phone-input"
                        v-model="form.phone"
                        class="mt-1 block w-full"
                        placeholder="11999999999"
                    />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        data-testid="email-input"
                        v-model="form.email"
                        class="mt-1 block w-full"
                        placeholder="email@exemplo.com"
                    />
                    <InputError v-if="errors.email" :message="errors.email" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="notes" value="Observações" />
                    <textarea
                        id="notes"
                        data-testid="notes-input"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="Anotações sobre o cliente..."
                    ></textarea>
                </div>

                <div v-if="isEdit" class="flex items-center">
                    <input
                        id="is_active"
                        type="checkbox"
                        data-testid="is-active-input"
                        v-model="form.is_active"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Cliente ativo</label>
                </div>

                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    <PrimaryButton type="submit" data-testid="submit-button" :disabled="isSubmitting">
                        {{ isEdit ? 'Salvar' : 'Criar Cliente' }}
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="handleClose">
                        Cancelar
                    </SecondaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>