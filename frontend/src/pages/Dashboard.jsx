import { useEffect, useState } from 'react';
import axios from 'axios';

const statConfig = [
  {
    key: 'total_tickets',
    label: 'Total Tickets',
    bg: 'bg-blue-50',
    text: 'text-blue-800',
    border: 'border-blue-200',
  },
  {
    key: 'open_tickets',
    label: 'Open Tickets',
    bg: 'bg-yellow-50',
    text: 'text-yellow-800',
    border: 'border-yellow-200',
  },
  {
    key: 'resolved_tickets',
    label: 'Resolved Tickets',
    bg: 'bg-green-50',
    text: 'text-green-800',
    border: 'border-green-200',
  },
  {
    key: 'sla_breached',
    label: 'SLA Breached',
    bg: 'bg-red-50',
    text: 'text-red-800',
    border: 'border-red-200',
  },
];

export default function Dashboard() {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;

    async function fetchMetrics() {
      try {
        const res = await axios.get('/api/dashboard');
        if (!cancelled) {
          setMetrics(res.data.data);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err.response?.data?.message || err.message || 'Failed to load metrics');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    fetchMetrics();

    return () => {
      cancelled = true;
    };
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {error}
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-slate-800 mb-6">Dashboard</h1>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statConfig.map((stat) => (
          <div
            key={stat.key}
            className={`rounded-xl border ${stat.border} bg-white shadow-sm p-6 flex flex-col items-start`}
          >
            <div className={`inline-flex items-center justify-center rounded-lg ${stat.bg} ${stat.text} w-10 h-10 mb-4 text-lg font-bold`}>
              {metrics[stat.key] ?? 0}
            </div>
            <div className={`text-3xl font-bold ${stat.text} mb-1`}>
              {metrics[stat.key] ?? 0}
            </div>
            <div className="text-sm text-slate-600">{stat.label}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
