---
status: pending
file: resources/js/Pages/Dashboard/Cashbox.vue
line: 266
severity: medium
author: claude-code
provider_ref:
---

# Issue 020: Cashbox Input Accepts Raw Numbers Without Currency Formatting

## Review Comment

Money inputs in `Cashbox.vue` accept raw numeric strings (e.g., "5000") without displaying the BRL currency format during input. Users see raw integers instead of "R$ 50,00". This creates confusion and risk of input errors.

**Fix:** Implement a currency-aware input component:

```vue
<TextInput
  id="open-balance"
  v-model="formattedAmount"
  @input="parseCurrency"
  placeholder="R$ 0,00"
/>

<script setup>
import { useCurrencyInput } from '@/composables/useCurrencyInput';
const { formattedAmount, parsedAmount } = useCurrencyInput();
</script>
```

The `formatPrice()` function exists in `AppointmentModal.vue` but isn't used in the Cashbox inputs. Create a shared `useCurrencyInput` composable or use the `currency-input` component pattern from AppointmentModal.

## Triage

- Decision: `UNREVIEWED`
- Notes: