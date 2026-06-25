import { defineStore } from 'pinia';
import { pharmacogeneticsApi } from '@/api/pharmacogenetics';

export const usePharmacogeneticsStore = defineStore('pharmacogenetics', {
    state: () => ({
        // Загруженные маркеры с добавленным полем editingValueId
        markers: [],
        // Снимок данных для отката при отмене редактирования
        originalMarkersJson: '',
        // Режим редактирования
        editing: false,
        // Ошибка сохранения
        saveError: null,
        // Индикатор загрузки
        loading: false,
        // Текущий пациент и препарат
        patientId: null,
        drugIri: null,
    }),

    actions: {
        /**
         * Загрузка (или перезагрузка) данных фармакогенетики.
         * @param {string|number} patientId
         * @param {string|null} drugIri
         */
        async fetchPharmacogenetics(patientId, drugIri = null) {
            if (!patientId) return;

            this.patientId = patientId;
            this.drugIri = drugIri;
            this.loading = true;
            this.saveError = null;

            try {
                const params = {};
                if (drugIri) {
                    params.drug = drugIri;
                }
                const response = await pharmacogeneticsApi.getForPatient(patientId, { params });
                this.markers = (response.data.markers || []).map(marker => ({
                    ...marker,
                    editingValueId: marker.currentValueId,
                }));
                this.originalMarkersJson = JSON.stringify(this.markers);
            } catch (err) {
                console.error('Ошибка загрузки фармакогенетики:', err);
                this.markers = [];
            } finally {
                this.loading = false;
            }
        },

        /** Переход в режим редактирования */
        startEditing() {
            this.markers.forEach(m => {
                m.editingValueId = m.currentValueId;
            });
            this.originalMarkersJson = JSON.stringify(this.markers);
            this.editing = true;
            this.saveError = null;
        },

        /** Отмена редактирования с восстановлением исходных данных */
        cancelEditing() {
            const originalMarkers = JSON.parse(this.originalMarkersJson || '[]');
            this.markers = this.markers.map(marker => {
                const original = originalMarkers.find(m => m.markerId === marker.markerId);
                if (original) {
                    return {
                        ...marker,
                        currentValueId: original.currentValueId,
                        currentValue: original.currentValue,
                        editingValueId: original.currentValueId,
                        resultId: original.resultId,
                    };
                }
                return marker;
            });
            this.editing = false;
            this.saveError = null;
        },

        /** Сохранение изменений */
        async save() {
            this.saveError = null;
            const requests = [];

            for (const marker of this.markers) {
                const original = this.getOriginalMarker(marker.markerId);
                const newValueId = marker.editingValueId;
                const originalValueId = original ? original.currentValueId : null;
                const hasResult = !!marker.resultId;

                // Нет результата и генотип не выбран — пропускаем
                if (!hasResult && (newValueId === null || newValueId === undefined)) {
                    continue;
                }

                // Есть результат, но генотип удалён — удаляем запись
                if (hasResult && (newValueId === null || newValueId === undefined)) {
                    requests.push(pharmacogeneticsApi.deleteResult(marker.resultId));
                    continue;
                }

                // Проверяем, изменилось ли значение генотипа
                const valueChanged = newValueId !== originalValueId;
                if (!valueChanged) {
                    continue;
                }

                const payload = {
                    patient: `/api/patients/${this.patientId}`,
                    marker: `/api/genetic_markers/${marker.markerId}`,
                    markerValue: `/api/genetic_marker_values/${newValueId}`,
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
                await this.fetchPharmacogenetics(this.patientId, this.drugIri);
                this.editing = false;
            } catch (err) {
                console.error('Ошибка сохранения фармакогенетики:', err);
                this.saveError = 'Не удалось сохранить фармакогенетические данные.';
                await this.fetchPharmacogenetics(this.patientId, this.drugIri);
            }
        },

        /**
         * Найти исходный маркер в снимке по markerId
         * @param {number} markerId
         * @returns {object|null}
         */
        getOriginalMarker(markerId) {
            const originalMarkers = JSON.parse(this.originalMarkersJson || '[]');
            return originalMarkers.find(m => m.markerId === markerId) || null;
        },

        /**
         * Получить лейбл генотипа для отображения.
         * Можно оставить в компоненте, но для удобства вынесен сюда.
         * @param {object} marker
         * @returns {string|null}
         */
        getGenotypeLabel(marker) {
            if (!marker.currentValueId) return null;
            const option = marker.possibleValues.find(opt => opt.id === marker.currentValueId);
            return option ? `${option.label}: ${option.description}` : (marker.currentValue || '');
        },
    },
});