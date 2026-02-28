import { useState } from 'react';
import ScheduleList from './ScheduleList';
import ScheduleForm from './ScheduleForm';
import SettingsPanel from './SettingsPanel';

export default function App() {
  const [refreshToken, setRefreshToken] = useState(0);

  return (
    <div className="fcs-admin-app">
      <ScheduleForm onSaved={() => setRefreshToken((v) => v + 1)} />
      <ScheduleList refreshToken={refreshToken} />
      <SettingsPanel />
    </div>
  );
}
