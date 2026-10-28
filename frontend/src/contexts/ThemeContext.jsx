import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { useAuth } from './AuthContext';

const ThemeContext = createContext({ isDark: false, toggleTheme: () => {} });

const getUserId = (user) => user?.id ?? user?.Id ?? null;

const storageKey = (user) => (getUserId(user) ? `gacela_theme_${getUserId(user)}` : null);

const readTheme = (user) => {
  const key = storageKey(user);
  return key && localStorage.getItem(key) === 'dark' ? 'dark' : 'light';
};

export const ThemeProvider = ({ children }) => {
  const { user } = useAuth();
  const userId = getUserId(user);
  const [theme, setTheme] = useState(() => readTheme(user));

  // Sin sesión (login) siempre claro; con sesión se carga la preferencia del usuario
  useEffect(() => {
    setTheme(readTheme(user));
  }, [userId]);

  useEffect(() => {
    document.documentElement.classList.toggle('dark', Boolean(userId) && theme === 'dark');
  }, [theme, userId]);

  const toggleTheme = useCallback(() => {
    setTheme((current) => {
      const next = current === 'dark' ? 'light' : 'dark';
      const key = storageKey(user);
      if (key) localStorage.setItem(key, next);
      return next;
    });
  }, [userId]);
  const value = useMemo(() => ({ isDark: theme === 'dark', toggleTheme }), [theme, toggleTheme]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export const useTheme = () => useContext(ThemeContext);
