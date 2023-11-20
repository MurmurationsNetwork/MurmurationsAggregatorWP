import { iterateObject } from '../utils/iterateObject'
import PropTypes from 'prop-types'

export default function Directory({ profiles }) {
  return (
    <div className="max-w-screen-md mx-auto">
      <ul>
        {profiles.map(profile => (
          <li key={profile.id} className="py-4">
            <p className="text-lg font-bold mb-2">Title: {profile.name}</p>
            <div className="space-y-2">
              {Object.entries(iterateObject(profile.profile_data)).map(
                ([key, value]) => (
                  <p key={key} className="text-sm">
                    {key}: {value}
                  </p>
                )
              )}
            </div>
            {profile.profile_data.image && (
              <img
                src={profile.profile_data.image}
                alt="Profile Image"
                className="mt-4 w-52 h-52"
                onError={e => {
                  e.target.style.display = 'none'
                }}
              />
            )}
          </li>
        ))}
      </ul>
    </div>
  )
}

Directory.propTypes = {
  profiles: PropTypes.array.isRequired
}
