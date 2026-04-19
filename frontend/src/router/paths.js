export const HOME_PATH = '/';
export const LOGIN_PATH = '/login';
export const PATIENT_ADD_PATH = '/patient/add';

export const PATIENT_HISTORY_TEMPLATE = '/patient/:patientId';
export const TREATMENT_ADD_TEMPLATE = '/patient/:patientId/treatment/add';

export const PATIENT_HISTORY = (patientId) => `/patient/${patientId}`;
export const TREATMENT_ADD = (patientId) => `/patient/${patientId}/treatment/add`;