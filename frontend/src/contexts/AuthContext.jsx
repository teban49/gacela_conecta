import { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    checkAuth();
    
    // Validar sesión cada 5 minutos para detectar cambios en estado/permisos
    const interval = setInterval(validateSession, 5 * 60 * 1000);
    
    return () => clearInterval(interval);
  }, []);

  const checkAuth = async () => {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');

    if (token && userData) {
      try {
        const parsedUser = JSON.parse(userData);
        setUser(parsedUser);
        setIsAuthenticated(true);
        
        // Validar sesión con el backend
        await validateSession();
      } catch (error) {
        console.error('Error parsing user data:', error);
        logout();
      }
    }
    setLoading(false);
  };

  const validateSession = async () => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      logout();
      return;
    }

    try {
      const response = await authAPI.getMe();
      if (response.data?.success && response.data?.data) {
        const currentUser = response.data.data;
        
        // Verificar si el usuario fue desactivado
        if (!currentUser.Estado) {
          console.warn('Usuario desactivado por administrador');
          logout();
          return;
        }
        
        // Actualizar datos del usuario con permisos actualizados
        setUser(currentUser);
        localStorage.setItem('user_data', JSON.stringify(currentUser));
      }
    } catch (error) {
      console.error('Error validando sesión:', error);
      if (error.response?.status === 401) {
        logout();
      }
    }
  };

  const login = (token, userData) => {
    localStorage.setItem('auth_token', token);
    localStorage.setItem('user_data', JSON.stringify(userData));
    setUser(userData);
    setIsAuthenticated(true);
  };

  const logout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    setUser(null);
    setIsAuthenticated(false);
  };

  const hasPermission = (permission) => {
    if (!user || !user.permissions) return false;
    return user.permissions.includes(permission);
  };

  const hasRole = (role) => {
    if (!user || !user.roles) return false;
    // roles puede ser array de objetos o strings
    const roles = Array.isArray(user.roles) ? user.roles : [];
    return roles.some(r => {
      const roleCode = typeof r === 'object' ? r.Codigo : r;
      const roleName = typeof r === 'object' ? r.Nombre : '';
      return roleCode === role || roleName === role;
    });
  };

  const refreshUserData = async () => {
    await validateSession();
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        isAuthenticated,
        login,
        logout,
        hasPermission,
        hasRole,
        refreshUserData,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
