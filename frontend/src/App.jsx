import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import TicketBoard from './pages/TicketBoard';
import TicketDetail from './pages/TicketDetail';

function Layout() {
  const { user, logout } = useAuth();

  return (
    <div className="min-h-screen flex flex-col">
      <header className="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
              PD
            </div>
            <span className="font-semibold text-slate-800 text-lg">PulseDesk</span>
          </div>
          {user && (
            <div className="flex items-center gap-4">
              <span className="text-sm text-slate-600 hidden sm:inline">
                {user.name} · <span className="capitalize">{user.role}</span>
              </span>
              <button
                onClick={logout}
                className="text-sm text-slate-600 hover:text-slate-900 font-medium"
              >
                Logout
              </button>
            </div>
          )}
        </div>
      </header>
      <main className="flex-1">
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route
            path="/tickets"
            element={
              <ProtectedRoute>
                <TicketBoard />
              </ProtectedRoute>
            }
          />
          <Route
            path="/tickets/:id"
            element={
              <ProtectedRoute>
                <TicketDetail />
              </ProtectedRoute>
            }
          />
          <Route
            path="/"
            element={
              user ? <Navigate to="/tickets" replace /> : <Navigate to="/login" replace />
            }
          />
        </Routes>
      </main>
    </div>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Layout />
      </AuthProvider>
    </BrowserRouter>
  );
}
