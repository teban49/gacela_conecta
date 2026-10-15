import { useState, useEffect, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Loader2, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { authAPI } from '@/services/api';
import { useAuth } from '@/contexts/AuthContext';

const VerifyPage = () => {
  const [searchParams] = useSearchParams();
  const [status, setStatus] = useState('loading'); // loading, success, error
  const [message, setMessage] = useState('');
  const navigate = useNavigate();
  const { login } = useAuth();

  // 🔒 Bandera para impedir peticiones duplicadas en React StrictMode
  const hasRequested = useRef(false);

  useEffect(() => {
    const verifyToken = async () => {
      const token = searchParams.get('token');

      if (!token) {
        setStatus('error');
        setMessage('Token no proporcionado. Por favor solicita un nuevo enlace de acceso.');
        return;
      }

      // Si ya se disparó la petición anteriormente, ignoramos llamados posteriores
      if (hasRequested.current) return;
      hasRequested.current = true;

      try {
        const response = await authAPI.verifyToken(token);

        if (response.data.success) {
          login(response.data.data.token, response.data.data.user);
          setStatus('success');
          setMessage('Autenticación exitosa. Redirigiendo al dashboard...');
          
          setTimeout(() => {
            navigate('/dashboard', { replace: true });
          }, 1500);
        } else {
          setStatus('error');
          setMessage(response.data.message || 'Error al verificar el token.');
        }
      } catch (err) {
        setStatus('error');
        setMessage(err.response?.data?.message || 'El enlace de acceso es inválido o ha expirado.');
      }
    };

    verifyToken();
  }, [searchParams, login, navigate]);

  const getStatusIcon = () => {
    switch (status) {
      case 'loading':
        return <Loader2 className="w-16 h-16 text-indigo-500 animate-spin" />;
      case 'success':
        return <CheckCircle className="w-16 h-16 text-green-500" />;
      case 'error':
        return <XCircle className="w-16 h-16 text-red-500" />;
      default:
        return <AlertCircle className="w-16 h-16 text-yellow-500" />;
    }
  };

  const getStatusColor = () => {
    switch (status) {
      case 'loading':
        return 'text-indigo-600';
      case 'success':
        return 'text-green-600';
      case 'error':
        return 'text-red-600';
      default:
        return 'text-yellow-600';
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-50 to-pink-100 flex items-center justify-center p-4">
      <div className="max-w-md w-full">
        <div className="bg-white rounded-2xl shadow-xl p-8 text-center">
          {/* Icon */}
          <div className="flex justify-center mb-6">
            {getStatusIcon()}
          </div>

          {/* Title */}
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            {status === 'loading' && 'Verificando acceso...'}
            {status === 'success' && '¡Acceso concedido!'}
            {status === 'error' && 'Error de autenticación'}
          </h1>

          {/* Message */}
          <p className={`text-sm ${getStatusColor()} mb-6`}>
            {message}
          </p>

          {/* Actions */}
          {status === 'error' && (
            <button
              onClick={() => navigate('/login')}
              className="bg-gradient-to-r from-indigo-500 to-purple-600 text-white py-3 px-6 rounded-lg font-medium hover:from-indigo-600 hover:to-purple-700 transition-all"
            >
              Solicitar nuevo enlace
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default VerifyPage;