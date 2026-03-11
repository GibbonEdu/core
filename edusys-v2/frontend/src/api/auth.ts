import apiClient from './client'
import axios from 'axios'

export interface LoginByPasswordParams {
  username: string
  password: string
}

export interface LoginBySMSParams {
  phone: string
  code: string
}

export interface TokenResponse {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export interface UserInfo {
  id: number
  username: string
  full_name: string
  email?: string
  phone?: string
  role?: string
  avatar?: string
}

export const authApi = {
  loginByPassword: (params: LoginByPasswordParams) =>
    apiClient.post<TokenResponse>('/auth/login/password', params).then(r => r.data),

  loginBySMS: (params: LoginBySMSParams) =>
    apiClient.post<TokenResponse>('/auth/login/sms', params).then(r => r.data),

  sendSMSCode: (phone: string) =>
    apiClient.post('/auth/sms/send', { phone }).then(r => r.data),

  getMe: () =>
    apiClient.get<UserInfo>('/auth/me').then(r => r.data),

  logout: () =>
    apiClient.post('/auth/logout').then(r => r.data),
}
