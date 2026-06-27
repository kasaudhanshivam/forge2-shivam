import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';

const statusColors = {
  open: 'bg-blue-100 text-blue-800',
  pending: 'bg-yellow-100 text-yellow-800',
  resolved: 'bg-green-100 text-green-800',
  closed: 'bg-slate-100 text-slate-700',
};

const priorityColors = {
  low: 'bg-slate-100 text-slate-700',
  medium: 'bg-blue-100 text-blue-800',
  high: 'bg-orange-100 text-orange-800',
  urgent: 'bg-red-100 text-red-800',
};

export default function TicketDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [ticket, setTicket] = useState(null);
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [commentBody, setCommentBody] = useState('');
  const [isInternal, setIsInternal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';
  const canMakeInternal = user?.role === 'agent' || user?.role === 'admin';

  const fetchTicket = async () => {
    try {
      const res = await axios.get(`${apiUrl}/api/tickets/${id}`);
      setTicket(res.data.data);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load ticket');
    }
  };

  const fetchComments = async () => {
    try {
      const res = await axios.get(`${apiUrl}/api/tickets/${id}/comments`);
      setComments(res.data.data || []);
    } catch (err) {
      // comments might be empty — not fatal
    }
  };

  useEffect(() => {
    setLoading(true);
    setError('');
    Promise.all([fetchTicket(), fetchComments()]).finally(() => setLoading(false));
  }, [id]);

  const handleAddComment = async (e) => {
    e.preventDefault();
    if (!commentBody.trim()) return;
    setSubmitting(true);
    try {
      await axios.post(`${apiUrl}/api/tickets/${id}/comments`, {
        body: commentBody,
        is_internal: canMakeInternal ? isInternal : false,
      });
      setCommentBody('');
      setIsInternal(false);
      await fetchComments();
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to add comment');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error && !ticket) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 mb-4">{error}</div>
        <button
          onClick={() => navigate('/tickets')}
          className="text-blue-600 hover:underline text-sm font-medium"
        >
          ← Back to tickets
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Back link */}
      <button
        onClick={() => navigate('/tickets')}
        className="text-blue-600 hover:underline text-sm font-medium mb-4"
      >
        ← Back to tickets
      </button>

      {/* Ticket details */}
      <div className="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div className="flex flex-wrap items-start gap-3 mb-3">
          <span
            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[ticket?.status] || 'bg-slate-100 text-slate-700'}`}
          >
            {ticket?.status}
          </span>
          <span
            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${priorityColors[ticket?.priority] || 'bg-slate-100 text-slate-700'}`}
          >
            {ticket?.priority}
          </span>
          {ticket?.tags?.length > 0 &&
            ticket.tags.map((tag) => (
              <span
                key={tag}
                className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600"
              >
                {tag}
              </span>
            ))}
        </div>

        <h1 className="text-2xl font-semibold text-slate-800 mb-2">{ticket?.subject}</h1>
        <p className="text-slate-600 text-sm leading-relaxed mb-4">{ticket?.description}</p>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <span className="text-slate-500">Requester: </span>
            <span className="font-medium text-slate-700">{ticket?.requester?.name}</span>
          </div>
          <div>
            <span className="text-slate-500">Assignee: </span>
            <span className="font-medium text-slate-700">
              {ticket?.assignee?.name || <span className="text-slate-400">Unassigned</span>}
            </span>
          </div>
          <div>
            <span className="text-slate-500">Created: </span>
            <span className="font-medium text-slate-700">
              {ticket?.created_at && new Date(ticket.created_at).toLocaleString()}
            </span>
          </div>
        </div>
      </div>

      {/* Comments */}
      <div className="bg-white rounded-xl border border-slate-200 p-6">
        <h2 className="text-lg font-semibold text-slate-800 mb-4">Conversation</h2>

        {comments.length === 0 ? (
          <p className="text-slate-500 text-sm mb-6">No comments yet.</p>
        ) : (
          <div className="space-y-4 mb-6">
            {comments.map((comment) => (
              <div
                key={comment.id}
                className={`p-4 rounded-lg border ${comment.is_internal ? 'bg-yellow-50 border-yellow-200' : 'bg-slate-50 border-slate-200'}`}
              >
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-medium text-sm text-slate-800">{comment.user?.name}</span>
                  <span className="text-xs text-slate-500">
                    {new Date(comment.created_at).toLocaleString()}
                  </span>
                  {comment.is_internal && (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                      Internal
                    </span>
                  )}
                </div>
                <p className="text-sm text-slate-700 whitespace-pre-wrap">{comment.body}</p>
              </div>
            ))}
          </div>
        )}

        {/* Comment form */}
        <form onSubmit={handleAddComment} className="space-y-3">
          <textarea
            value={commentBody}
            onChange={(e) => setCommentBody(e.target.value)}
            placeholder="Write a comment..."
            rows={3}
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            required
          />
          {canMakeInternal && (
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                checked={isInternal}
                onChange={(e) => setIsInternal(e.target.checked)}
                className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
              />
              Mark as internal note
            </label>
          )}
          <div className="flex justify-end">
            <button
              type="submit"
              disabled={submitting || !commentBody.trim()}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            >
              {submitting ? 'Posting...' : 'Post Comment'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
