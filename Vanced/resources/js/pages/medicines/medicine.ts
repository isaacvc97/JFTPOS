export interface Dosage {
    id?: number | null;
    concentration: string;
    form: Form;
    presentations: Array<Presentation>;
}
export interface Presentation {
    id?: number | null;
    medicine_dosage_id: number;
    barcode: string;
    quantity?: number | null;
    cost: number;
    price: number;
    stock: number;
    stock_alert?: number | null;
    unit_type: number;
}
export interface Form {
  id?: number | null;
  name: string;
  image?: string | null;
}

export interface Medicine {
    id: number | null;
    name: string;
    generic_name: string;
    description?: string;
    laboratory_id: number;
    image_url?: string;
    dosages: Array<Dosage>;
  }


export function emptyMedicine(): Medicine {
    return {
        id: 0,
        name: "",
        generic_name: "",
        description: "",
        laboratory_id: 0,
        dosages: []
    };
}
export function emptyDosage(): Dosage {
    return {
        id: null,
        concentration: "",
        form: { name: "" },
        presentations: []
    };
}
export function emptyPresentation(): Presentation {
    return {
        id: null,
        medicine_dosage_id: 0,
        barcode: "",
        cost: 0,
        price: 0,
        stock: 0,
        stock_alert: null,
        unit_type: 0
    };
}
export function emptyForm(): Form {
    return {
      id: null,
      name: "",
      image: ""
    };
}
export function emptyMedicineWithDosage(): Medicine {
    return {
        ...emptyMedicine(),
        dosages: [emptyDosage()]
    };
}
export function emptyMedicineWithPresentation(): Medicine {
    return {
        ...emptyMedicineWithDosage(),
        dosages: [
            {
                ...emptyDosage(),
                presentations: [emptyPresentation()]
            }
        ]
    };
}
export function emptyMedicineWithForm(): Medicine {
    return {
        ...emptyMedicineWithDosage(),
        dosages: [
            {
                ...emptyDosage(),
                form: emptyForm()
            }
        ]
    };
}
export function emptyMedicineWithAll(): Medicine {
    return {
        ...emptyMedicineWithDosage(),
        dosages: [
            {
                ...emptyDosage(),
                form: emptyForm(),
                presentations: [emptyPresentation()]
            }
        ]
    };
}
export function isEmptyMedicine(medicine: Medicine): boolean {
    return (
        !medicine.id &&
        !medicine.name &&
        !medicine.generic_name &&
        (!medicine.description || medicine.description.trim() === "") &&
        !medicine.laboratory_id &&
        (!medicine.dosages || medicine.dosages.length === 0)
    );
}
export function isEmptyDosage(dosage: Medicine["dosages"][number]): boolean {
    return (
        !dosage.id &&
        !dosage.concentration &&
        (!dosage.form || !dosage.form.name) &&
        (!dosage.presentations || dosage.presentations.length === 0)
    );
}
export function isEmptyPresentation(presentation: Presentation): boolean {
    return (
        !presentation.id &&
        !presentation.barcode &&
        presentation.price === 0 &&
        presentation.stock === 0 &&
        (presentation.stock_alert === null || presentation.stock_alert === undefined)
    );
}
export function isEmptyForm(form: Form): boolean {
    return (
        !form.name
    );
}
export function isEmptyMedicineWithDosage(medicine: Medicine): boolean {
    return (
        isEmptyMedicine(medicine) &&
        medicine.dosages &&
        medicine.dosages.length === 1 &&
        isEmptyDosage(medicine.dosages[0])
    );
}
export function isEmptyMedicineWithPresentation(medicine: Medicine): boolean {
    return (
        isEmptyMedicineWithDosage(medicine) &&
        medicine.dosages &&
        medicine.dosages[0].presentations &&
        medicine.dosages[0].presentations.length === 1 &&
        isEmptyPresentation(medicine.dosages[0].presentations[0])
    );
}
export function isEmptyMedicineWithForm(medicine: Medicine): boolean {
    return (
        isEmptyMedicineWithDosage(medicine) &&
        medicine.dosages &&
        medicine.dosages[0].form &&
        isEmptyForm(medicine.dosages[0].form)
    );
}
export function isEmptyMedicineWithAll(medicine: Medicine): boolean {
    return (
        isEmptyMedicineWithDosage(medicine) &&
        medicine.dosages &&
        medicine.dosages[0].form &&
        isEmptyForm(medicine.dosages[0].form) &&
        medicine.dosages[0].presentations &&
        medicine.dosages[0].presentations.length === 1 &&
        isEmptyPresentation(medicine.dosages[0].presentations[0])
    );
}
export function isEmptyMedicineWithAllAndId(medicine: Medicine): boolean {
    return (
        isEmptyMedicineWithAll(medicine) &&
        medicine.id === null
    );
}
export function isValidMedicine(medicine: Medicine): boolean {
    return (
        medicine.name.trim() !== "" &&
        medicine.generic_name.trim() !== "" &&
        medicine.dosages.length > 0 &&
        medicine.dosages.every(dosage => {
            return (
                dosage.concentration.trim() !== "" &&
                dosage.form && dosage.form.name.trim() !== "" &&
                dosage.presentations.length > 0 &&
                dosage.presentations.every(presentation => {
                    return (
                        presentation.barcode.trim() !== "" &&
                        presentation.price > 0 &&
                        presentation.stock >= 0
                    );
                })
            );
        })
    );
}
export function isValidDosage(dosage: Medicine["dosages"][number]): boolean {
    return (
        dosage.concentration.trim() !== "" &&
        dosage.form && dosage.form.name.trim() !== "" &&
        dosage.presentations.length > 0 &&
        dosage.presentations.every(presentation => {
            return (
                presentation.barcode.trim() !== "" &&
                presentation.price > 0 &&
                presentation.stock >= 0
            );
        })
    );
}
export function isValidPresentation(presentation: Medicine["dosages"][number]["presentations"][number]): boolean {
    return (
        presentation.barcode.trim() !== "" &&
        presentation.price > 0 &&
        presentation.stock >= 0
    );
}
export function isValidForm(form: Medicine["dosages"][number]["form"]): boolean {
    return (
        form.name.trim() !== ""
    );
}