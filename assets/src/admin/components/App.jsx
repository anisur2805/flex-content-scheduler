import { useState } from 'react';
import ScheduleList from './ScheduleList';
import ScheduleForm from './ScheduleForm';
import SettingsPanel from './SettingsPanel';

/**
 * Root admin application component.
 *
 * @since 1.0.0
 *
 * @return {JSX.Element} Admin app UI.
 */
export default function App() {
  const [refreshToken, setRefreshToken] = useState(0);

  return (
    <div className="flex-cs-admin-app">
      <ScheduleForm onSaved={() => setRefreshToken((v) => v + 1)} />
      <ScheduleList refreshToken={refreshToken} />
      <SettingsPanel />
    </div>
  );
}
