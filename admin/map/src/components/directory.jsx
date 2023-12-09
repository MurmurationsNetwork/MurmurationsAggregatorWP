import { iterateObject } from '../utils/iterateObject'
import PropTypes from 'prop-types'

export default function Directory({ profiles, linkType }) {
  return (
    <div className="max-w-screen-md mx-auto">
      <ul className="divide-y divide-gray-300">
        {profiles.map((profile, index) => (
          <div key={index}>
            <li key={profile.id} className="py-4 dir-item">
              {profile.profile_data.image && (
                <img
                  src={profile.profile_data.image}
                  alt="Profile Image"
                  className="mb-4 max-h-16"
                  onError={e => {
                    e.target.style.display = 'none'
                  }}
                />
              )}
              <p className="text-lg font-bold mb-2">{profile.name}</p>
              <div className="space-y-2">
                <p className="text-sm truncate">
                  {linkType === 'wp' ? (
                    <a href={profile.post_url}>More...</a>
                  ) : (
                    <span>
                      More:&nbsp;
                      <a
                        href={profile.profile_data.primary_url}
                        target="_blank"
                        rel="noreferrer"
                      >
                        {profile.profile_data.primary_url}
                      </a>
                    </span>
                  )}
                </p>
                {Object.entries(iterateObject(profile.profile_data)).map(
                  ([key, value]) => (
                    <p key={key} className="text-sm truncate">
                      {key}: {value}
                    </p>
                  )
                )}
              </div>
            </li>
            <hr className="my-4" />
          </div>
        ))}
      </ul>
    </div>
  )
}

Directory.propTypes = {
  profiles: PropTypes.array.isRequired,
  linkType: PropTypes.string.isRequired
}
