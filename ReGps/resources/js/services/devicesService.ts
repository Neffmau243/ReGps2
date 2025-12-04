// ============================================
// Devices Service - Device management endpoints
// ============================================

import apiClient from './api';
import type { Device, CreateDeviceRequest, UpdateDeviceRequest } from '@/types';

export const devicesService = {
  // Get all devices (filtered by role)
  async getAll(): Promise<Device[]> {
    const response = await apiClient.get('/dispositivos');
    
    const dispositivos = Array.isArray(response.data) ? response.data : [];
    
    // Transform backend data to frontend format
    const transformed = dispositivos.map((dispositivo: any) => ({
      id: dispositivo.DispositivoID,
      name: dispositivo.Modelo || `Dispositivo ${dispositivo.DispositivoID}`,
      serial: dispositivo.IMEI,
      status: dispositivo.Estado?.toLowerCase() || 'inactivo',
      user_id: dispositivo.EmpleadoID,
      created_at: dispositivo.created_at,
      updated_at: dispositivo.updated_at,
    }));
    
    return transformed;
  },

  // Get device by ID
  async getById(id: number): Promise<Device> {
    const response = await apiClient.get(`/dispositivos/${id}`);
    const dispositivo = response.data;
    
    return {
      id: dispositivo.DispositivoID,
      name: dispositivo.Modelo || `Dispositivo ${dispositivo.DispositivoID}`,
      serial: dispositivo.IMEI,
      status: dispositivo.Estado?.toLowerCase() || 'inactivo',
      user_id: dispositivo.EmpleadoID,
      created_at: dispositivo.created_at,
      updated_at: dispositivo.updated_at,
    };
  },

  // Create device (Admin only)
  async create(deviceData: CreateDeviceRequest): Promise<Device> {
    // Transform frontend data to backend format
    const backendData = {
      IMEI: deviceData.serial,
      Modelo: deviceData.name,
      Estado: deviceData.status.charAt(0).toUpperCase() + deviceData.status.slice(1),
      EmpleadoID: deviceData.user_id || null,
    };
    
    const response = await apiClient.post('/dispositivos', backendData);
    const dispositivo = response.data;
    
    return {
      id: dispositivo.DispositivoID,
      name: dispositivo.Modelo || `Dispositivo ${dispositivo.DispositivoID}`,
      serial: dispositivo.IMEI,
      status: dispositivo.Estado?.toLowerCase() || 'inactivo',
      user_id: dispositivo.EmpleadoID,
      created_at: dispositivo.created_at,
      updated_at: dispositivo.updated_at,
    };
  },

  // Update device (Admin only)
  async update(id: number, deviceData: UpdateDeviceRequest): Promise<Device> {
    // Transform frontend data to backend format
    const backendData: any = {};
    if (deviceData.serial) backendData.IMEI = deviceData.serial;
    if (deviceData.name) backendData.Modelo = deviceData.name;
    if (deviceData.status) backendData.Estado = deviceData.status.charAt(0).toUpperCase() + deviceData.status.slice(1);
    if (deviceData.user_id !== undefined) backendData.EmpleadoID = deviceData.user_id;
    
    const response = await apiClient.put(`/dispositivos/${id}`, backendData);
    const dispositivo = response.data;
    
    return {
      id: dispositivo.DispositivoID,
      name: dispositivo.Modelo || `Dispositivo ${dispositivo.DispositivoID}`,
      serial: dispositivo.IMEI,
      status: dispositivo.Estado?.toLowerCase() || 'inactivo',
      user_id: dispositivo.EmpleadoID,
      created_at: dispositivo.created_at,
      updated_at: dispositivo.updated_at,
    };
  },

  // Delete device (Admin only)
  async delete(id: number): Promise<void> {
    await apiClient.delete(`/dispositivos/${id}`);
  },

  // Change device status (Admin only)
  async changeStatus(id: number, status: 'activo' | 'inactivo' | 'mantenimiento'): Promise<Device> {
    const backendStatus = status.charAt(0).toUpperCase() + status.slice(1);
    const response = await apiClient.put(`/dispositivos/${id}`, { Estado: backendStatus });
    const dispositivo = response.data;
    
    return {
      id: dispositivo.DispositivoID,
      name: dispositivo.Modelo || `Dispositivo ${dispositivo.DispositivoID}`,
      serial: dispositivo.IMEI,
      status: dispositivo.Estado?.toLowerCase() || 'inactivo',
      user_id: dispositivo.EmpleadoID,
      created_at: dispositivo.created_at,
      updated_at: dispositivo.updated_at,
    };
  },
};
