import PropTypes from 'prop-types'

export default function DeletedData({ deletedProfiles }) {
  return (
    <div>
      {deletedProfiles.length > 0 && (
        <div>
          <h1>Deleted Data</h1>
          <table className="table-auto">
            <thead>
              <tr>
                <th className="px-4 py-2">id</th>
                <th className="px-4 py-2">Name</th>
                <th className="px-4 py-2">Description</th>
                <th className="px-4 py-2">URL</th>
              </tr>
            </thead>
            <tbody>
              {deletedProfiles.map((profile, index) => (
                <tr key={index}>
                  <td className="border px-4 py-2">{index + 1}</td>
                  <td className="border px-4 py-2">
                    {profile.profile_data.name}
                  </td>
                  <td className="border px-4 py-2">
                    {profile.profile_data.description}
                  </td>
                  <td className="border px-4 py-2">
                    {profile.index_data.profile_url}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}

DeletedData.propTypes = {
  deletedProfiles: PropTypes.array.isRequired
}
