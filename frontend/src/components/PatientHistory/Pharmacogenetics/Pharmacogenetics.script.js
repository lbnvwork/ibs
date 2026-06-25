import { usePharmacogeneticsStore } from '@/stores/pharmacogeneticsStore';

export default {
    name: 'Pharmacogenetics',
    props: {
        patientId: { type: [String, Number], required: true },
        drugIri: { type: String, default: null },
    },
    data() {
        return {
            store: usePharmacogeneticsStore(),
        };
    },
    watch: {
        patientId: {
            immediate: true,
            handler(newId) {
                if (newId) {
                    this.store.fetchPharmacogenetics(newId, this.drugIri);
                }
            },
        },
        drugIri(newIri) {
            if (this.patientId) {
                this.store.fetchPharmacogenetics(this.patientId, newIri);
            }
        },
    },
    methods: {
        getGenotypeLabel(marker) {
            return this.store.getGenotypeLabel(marker);
        },
    },
};