import { createContext, useContext, useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

  useEffect(() => {
    const token = localStorage.getItem('pulse_token');
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      try {
        const savedUser = localStorage.getItem('pulse_user');
        if (savedUser) {
          setUser(JSON.parse(savedUser));
        }
      } catch (e) {
        // ignore parse errors
      }
    }
    setLoading(false);

    // Response interceptor: catch 401 → clear auth and redirect to login
    const interceptor = axios.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('pulse_token');
          localStorage.removeItem('pulse_user');
          delete axios.defaults.headers.common['Authorization'];
          setUser(null);
          navigate('/login');
        }
        return Promise.reject(error);
      }
    );

    return () => {
      axios.interceptors.response.eject(interceptor);
    };
  }, [navigate]);

  const login = async (email, password) => {
    const res = await axios.post(`${apiUrl}/api/login`, { email, password });
    const { token, user: u } = res.data;
    localStorage.setItem('pulse_token', token);
    localStorage.setItem('pulse_user', JSON.stringify(u));
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    setUser(u);
    navigate('/tickets');
  };

  const register = async (name, email, password, organization_name) => {
    const res = await axios.post(`${apiUrl}/api/register`, {
      name,
      email,
      password,
      password_confirmation: password,
      organization_name,
    });
    const { token, user: u } = res.data;
    localStorage.setItem('pulse_token', token);
    localStorage.setItem('pulse_user', JSON.stringify(u));
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    setUser(u);
    navigate('/tickets');
  };

  const logout = async () => {
    try {
      await axios.post(`${apiUrl}/api/logout`);
    } catch (e) {
      // ignore errors on logout
    }
    localStorage.removeItem('pulse_token');
    localStorage.removeItem('pulse_user');
    delete axios.defaults.headers.common['Authorization'];
    setUser(null);
    navigate('/login');
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
