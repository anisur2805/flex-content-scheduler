import { createRoot } from 'react-dom/client';
import MetaBox from './MetaBox';
import './styles/metabox.scss';

const el = document.getElementById('fcs-metabox-root');
if (el) {
  const root = createRoot(el);
  root.render(<MetaBox />);
}
