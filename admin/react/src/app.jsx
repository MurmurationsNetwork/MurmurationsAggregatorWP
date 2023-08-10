import { useState } from 'react'

export default function App() {
  const [count, setCount] = useState(0)

  return (
    <div>
      <h3>React WP Admin</h3>

      <div className="react-wp-admin flex">
        <div>
          <div>
            <strong>Click the button below!</strong>
          </div>
          <button onClick={() => setCount(count + 1)}>Count: {count}</button>
        </div>
      </div>

      <pre>
        <strong>Start editing:</strong> admin/react/app.jsx
      </pre>

      <div>
        For documentation:{' '}
        <a
          href="https://github.com/dym5-official/react-wp-admin"
          rel="noreferrer"
          target="_blank"
        >
          https://github.com/dym5-official/react-wp-admin
        </a>
      </div>
    </div>
  )
}
