import React from 'react'
import TableRow from './TableRow'
import PropTypes from 'prop-types'

function Table({
  tableList,
  selectedIds,
  onSelectAll,
  onSelect,
  setIsPopupOpen,
  setOriginalJson,
  setModifiedJson
}) {
  const isAllSelected =
    selectedIds.length > 0 &&
    selectedIds.length ===
      tableList.filter(response => response.data.is_available).length

  return (
    <table className="shadow-md">
      <thead>
        <tr className="bg-gray-500 text-white">
          <th className="text-center p-2">
            <input
              type="checkbox"
              checked={isAllSelected}
              onChange={onSelectAll}
            />
          </th>
          <th className="text-center p-2">ID</th>
          <th className="text-center p-2">Geopoint</th>
          <th className="text-center p-2">Name/Title</th>
          <th className="text-center p-2">Profile URL</th>
          <th className="text-center p-2">Status</th>
          <th className="text-center p-2">Availability</th>
          <th className="text-center p-2"></th>
        </tr>
      </thead>
      <tbody className="striped">
        {tableList.map(response => (
          <TableRow
            key={response.id}
            response={response}
            isSelected={selectedIds.includes(response.id)}
            onSelect={onSelect}
            setIsPopupOpen={setIsPopupOpen}
            setOriginalJson={setOriginalJson}
            setModifiedJson={setModifiedJson}
          />
        ))}
      </tbody>
    </table>
  )
}

Table.propTypes = {
  tableList: PropTypes.arrayOf(PropTypes.object).isRequired,
  selectedIds: PropTypes.arrayOf(PropTypes.number).isRequired,
  onSelectAll: PropTypes.func.isRequired,
  onSelect: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}

export default Table
