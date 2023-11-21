import { iterateObject } from '../utils/iterateObject'
import PropTypes from 'prop-types'

export default function Directory({
  profiles,
  linkType,
  imageSize,
  imageSetSide
}) {
  let imageStyle
  if (imageSetSide === 'width') {
    imageStyle = { width: imageSize + 'vw', height: 'auto' }
  } else {
    imageStyle = { width: 'auto', height: imageSize + 'vh' }
  }

  return (
    <div className="max-w-screen-md mx-auto">
      <ul className="divide-y divide-gray-300">
        {profiles.map(profile => (
          <li key={profile.id} className="py-4">
            <p className="text-lg font-bold mb-2">Title: {profile.name}</p>
            <div className="space-y-2">
              <p className="text-sm truncate">
                More:&nbsp;
                {linkType === 'wp' ? (
                  <a href={profile.post_url} target="_blank" rel="noreferrer">
                    {profile.post_url}
                  </a>
                ) : (
                  <a
                    href={profile.profile_data.primary_url}
                    target="_blank"
                    rel="noreferrer"
                  >
                    {profile.profile_data.primary_url}
                  </a>
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
            {profile.profile_data.image && (
              <img
                src={profile.profile_data.image}
                alt="Profile Image"
                className="mt-4"
                style={imageStyle}
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
  profiles: PropTypes.array.isRequired,
  linkType: PropTypes.string.isRequired,
  imageSize: PropTypes.number.isRequired,
  imageSetSide: PropTypes.string.isRequired
}
