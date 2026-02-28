import { createRoot } from 'react-dom/client';
import App from './components/App';
import './styles/admin.scss';

const el = document.getElementById('fcs-admin-root');
if (el) {
  createRoot(el).render(<App />);
}
