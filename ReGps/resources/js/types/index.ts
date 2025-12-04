// Types for ReGPS Application

export interface Device {
  id: number;
  name: string;
  serial: string;
  status: 'activo' | 'inactivo' | 'mantenimiento';
  user_id: number | null;
  created_at?: string;
  updated_at?: string;
}

export interface CreateDeviceRequest {
  name: string;
  serial: string;
  status: 'activo' | 'inactivo' | 'mantenimiento';
  user_id?: number | null;
}

export interface UpdateDeviceRequest {
  name?: string;
  serial?: string;
  status?: 'activo' | 'inactivo' | 'mantenimiento';
  user_id?: number | null;
}

