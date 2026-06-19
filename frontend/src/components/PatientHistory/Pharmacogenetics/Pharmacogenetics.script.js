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
                const original = this.getOriginalMarker(marker.markerId);
                const newValueId = marker.editingValueId;
                const originalValueId = original ? original.currentValueId : null;
                const hasResult = !!marker.resultId;

                // Если нет результата и генотип не выбран – пропускаем
                if (!hasResult && (newValueId === null || newValueId === undefined)) {
                    continue;
                }

                // Если есть результат и генотип удалён – удаляем запись
                if (hasResult && (newValueId === null || newValueId === undefined)) {
                    requests.push(pharmacogeneticsApi.deleteResult(marker.resultId));
                    continue;
                }

                // Проверяем, изменилось ли значение, дата или комментарий
                const valueChanged = newValueId !== originalValueId;
                const dateChanged = (marker.editingTestDate || null) !== (original ? original.testDate || null : null);
                const commentChanged = (marker.editingComment || null) !== (original ? original.comment || null : null);

                if (!valueChanged && !dateChanged && !commentChanged) {
                    continue;
                }

                const payload = {
                    patient: `/api/patients/${this.patientId}`,
                    marker: `/api/genetic_markers/${marker.markerId}`,
                    markerValue: `/api/genetic_marker_values/${newValueId}`,
                    testDate: marker.editingTestDate || null,
                    comment: marker.editingComment || null
                };

                if (hasResult) {
                    requests.push(pharmacogeneticsApi.updateResult(marker.resultId, payload));
                } else {
                    requests.push(pharmacogeneticsApi.createResult(payload));
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

        getOriginalMarker(markerId) {
            const originalMarkers = JSON.parse(this.originalMarkersJson || '[]');
            return originalMarkers.find(m => m.markerId === markerId) || null;
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