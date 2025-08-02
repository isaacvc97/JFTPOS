import { usePage } from '@inertiajs/vue3'

export default {
  setup() {
    const page = usePage()

    // Acceder a los datos de la página actual
    const data = page.props.value.data

    // Acceder a los errores de validación
    const errors = page.props.value.errors

    // Acceder a otros datos de la página
    const flash = page.props.value.flash
    const url = page.url.value

    return {
      data,
      errors,
      flash,
      url
    }
  }
}