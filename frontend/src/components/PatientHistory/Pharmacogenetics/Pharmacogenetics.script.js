import { pharmacogeneticsApi } from '@/api/pharmacogenetics';

export default {
    name: 'Pharmacogenetics',
    props: {
        patientId: { type: [String, Number], required: true }
    },
    data() {
        return {
            markers: [],
            originalMarkersJson: '',
            editing: false,
            saveError: null
        };
    },
    watch: {
        patientId: {
            immediate: true,
            handler(newId) {
                if (newId) {
                    this.loadData();
                }
            }
        }
    },
    methods: {
        async loadData() {
            try {
                const response = await pharmacogeneticsApi.getForPatient(this.patientId);
                this.markers = (response.data.markers || []).map(marker => ({
                    ...marker,
                    editingValueId: marker.currentValueId,
                    editingTestDate: marker.testDate,
                    editingComment: marker.comment
                }));
                this.originalMarkersJson = JSON.stringify(this.markers);
                this.saveError = null;
            } catch (err) {
                console.error('Ошибка загрузки фармакогенетики:', err);
                this.markers = [];
            }
        },
        startEditing() {
            this.markers.forEach(m => {
                m.editingValueId = m.currentValueId;
                m.editingTestDate = m.testDate;
                m.editingComment = m.comment;
            });
            this.originalMarkersJson = JSON.stringify(this.markers);
            this.editing = true;
            this.saveError = null;
        },
        cancelEditing() {
            const originalMarkers = JSON.parse(this.originalMarkersJson || '[]');
            this.markers = this.markers.map(marker => {
                const original = originalMarkers.find(m => m.markerId === marker.markerId);
                if (original) {
                    return {
                        ...marker,
                        currentValueId: original.currentValueId,
                        currentValue: original.currentValue,
                        testDate: original.testDate,
                        editingValueId: original.currentValueId,
                        editingTestDate: original.testDate,
                        comment: original.comment,
                        editingComment: original.comment,
                        resultId: original.resultId
                    };
                }
                return marker;
            });
            this.editing = false;
            this.saveError = null;
        },
        async save() {
            this.saveError = null;
            const requests = [];

            for (const marker of this.markers) {
                const originalValueId = this.getOriginalValueId(marker.markerId);
                const newValueId = marker.editingValueId;

                if (newValueId === originalValueId) {
                    continue;
                }

                if (newValueId === null || newValueId === undefined) {
                    if (marker.resultId) {
                        requests.push(pharmacogeneticsApi.deleteResult(marker.resultId));
                    }
                } else {
                    const payload = {
                        patient: `/api/patients/${this.patientId}`,
                        marker: `/api/genetic_markers/${marker.markerId}`,
                        markerValue: `/api/genetic_marker_values/${newValueId}`,
                        testDate: marker.editingTestDate || null,
                        comment: marker.editingComment || null
                    };
                    if (marker.resultId) {
                        requests.push(pharmacogeneticsApi.updateResult(marker.resultId, payload));
                    } else {
                        requests.push(pharmacogeneticsApi.createResult(payload));
                    }
                }
            }

            if (requests.length === 0) {
                this.editing = false;
                return;
            }

            try {
                await Promise.all(requests);
                await this.loadData();
                this.editing = false;
            } catch (err) {
                console.error('Ошибка сохранения фармакогенетики:', err);
                this.saveError = 'Не удалось сохранить фармакогенетические данные.';
                await this.loadData();
            }
        },
        getGenotypeLabel(marker) {
            if (!marker.currentValueId) return null;
            const option = marker.possibleValues.find(opt => opt.id === marker.currentValueId);
            return option ? `${option.label}: ${option.description}` : (marker.currentValue || '');
        },
        getOriginalValueId(markerId) {
            const originalMarkers = JSON.parse(this.originalMarkersJson || '[]');
            const original = originalMarkers.find(m => m.markerId === markerId);
            return original ? original.currentValueId : null;
        }
    }
};