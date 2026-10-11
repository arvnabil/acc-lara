import { Navigate, useLocation } from '../compat/router';
import { useAuth } from '../compat/context';

/**
 * ProtectedRoute – redirect ke /login jika belum login.
 * Menyimpan URL tujuan di location.state.from agar bisa redirect balik setelah login.
 */
export default function ProtectedRoute({ children }) {
  const { user } = useAuth();
  const location = useLocation();

  if (!user) {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />;
  }

  return children;
}
